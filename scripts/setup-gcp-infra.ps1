# First-time GCP infrastructure for VentureLens (Cloud SQL + Artifact Registry + IAM)
param(
    [string]$ProjectId = $env:GCP_PROJECT_ID,
    [string]$Region = $env:GCP_REGION,
    [switch]$SkipSql
)

$ErrorActionPreference = "Stop"
. "$PSScriptRoot\load-env.ps1"

if (-not $ProjectId) { $ProjectId = $env:GOOGLE_CLOUD_PROJECT_ID }
if (-not $ProjectId) {
    Write-Error "Set GCP_PROJECT_ID or GOOGLE_CLOUD_PROJECT_ID in .env"
    exit 1
}
if (-not $Region) { $Region = "us-central1" }

$SqlInstance = "venturelens"
$DbName = "venturelens"
$DbUser = "venturelens"
$ArtifactRepo = "venturelens"

Write-Host "VentureLens GCP bootstrap"
Write-Host "  Project: $ProjectId"
Write-Host "  Region:  $Region"
Write-Host ""

gcloud config set project $ProjectId | Out-Null

Write-Host "Enabling APIs (may take 1-2 min)..."
$apis = @(
    "serviceusage.googleapis.com",
    "cloudresourcemanager.googleapis.com",
    "run.googleapis.com",
    "sqladmin.googleapis.com",
    "secretmanager.googleapis.com",
    "artifactregistry.googleapis.com",
    "iam.googleapis.com"
)
foreach ($api in $apis) {
    gcloud services enable $api --project=$ProjectId --quiet 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  enabled: $api"
    }
}

$prevErrorAction = $ErrorActionPreference
$ErrorActionPreference = "Continue"

Write-Host "Artifact Registry repository..."
$null = gcloud artifacts repositories describe $ArtifactRepo --location=$Region --project=$ProjectId 2>$null
if ($LASTEXITCODE -ne 0) {
    gcloud artifacts repositories create $ArtifactRepo `
        --repository-format=docker `
        --location=$Region `
        --project=$ProjectId `
        --description="VentureLens container images"
    Write-Host "  Created $ArtifactRepo"
} else {
    Write-Host "  Already exists: $ArtifactRepo"
}

$BucketName = "venturelens-uploads-$ProjectId"
Write-Host "Cloud Storage bucket..."
$null = gcloud storage buckets describe gs://$BucketName --project=$ProjectId 2>$null
if ($LASTEXITCODE -ne 0) {
    gcloud storage buckets create gs://$BucketName `
        --location=$Region `
        --project=$ProjectId `
        --uniform-bucket-level-access
    Write-Host "  Created bucket $BucketName"
} else {
    Write-Host "  Already exists: $BucketName"
}

if (-not $SkipSql) {
    if ([string]::IsNullOrWhiteSpace($env:DB_PASSWORD)) {
        Write-Error "Set DB_PASSWORD in .env before creating Cloud SQL (used for user $DbUser)"
        exit 1
    }

    Write-Host "Cloud SQL instance (db-f1-micro, first create ~10 min)..."
    $null = gcloud sql instances describe $SqlInstance --project=$ProjectId 2>$null
    if ($LASTEXITCODE -ne 0) {
        gcloud sql instances create $SqlInstance `
            --database-version=MYSQL_8_0 `
            --tier=db-f1-micro `
            --region=$Region `
            --project=$ProjectId `
            --storage-size=10GB `
            --storage-auto-increase `
            --root-password=$env:DB_PASSWORD
        Write-Host "  Created instance $SqlInstance"
    } else {
        Write-Host "  Already exists: $SqlInstance"
    }

    $null = gcloud sql databases describe $DbName --instance=$SqlInstance --project=$ProjectId 2>$null
    if ($LASTEXITCODE -ne 0) {
        gcloud sql databases create $DbName --instance=$SqlInstance --project=$ProjectId
        Write-Host "  Created database $DbName"
    }

    $null = gcloud sql users describe $DbUser --instance=$SqlInstance --project=$ProjectId 2>$null
    if ($LASTEXITCODE -ne 0) {
        gcloud sql users create $DbUser --instance=$SqlInstance --password=$env:DB_PASSWORD --project=$ProjectId
        Write-Host "  Created user $DbUser"
    } else {
        gcloud sql users set-password $DbUser --instance=$SqlInstance --password=$env:DB_PASSWORD --project=$ProjectId
        Write-Host "  Updated password for $DbUser"
    }
}

Write-Host "IAM for Cloud Run default service account..."
$projectNumber = gcloud projects describe $ProjectId --format="value(projectNumber)"
$computeSa = "${projectNumber}-compute@developer.gserviceaccount.com"

foreach ($role in @("roles/cloudsql.client", "roles/secretmanager.secretAccessor", "roles/storage.objectAdmin")) {
    gcloud projects add-iam-policy-binding $ProjectId `
        --member="serviceAccount:$computeSa" `
        --role=$role `
        --quiet 2>$null
    Write-Host "  $role -> $computeSa"
}

$ErrorActionPreference = $prevErrorAction

Write-Host ""
Write-Host "Bootstrap complete."
Write-Host "Next:"
Write-Host "  .\scripts\deploy-cloud-run.ps1 deploy"
