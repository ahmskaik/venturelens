# VentureLens Nightly Impact Archive Deployment Script (Gen 2 Cloud Function)
# Target OS: Windows PowerShell

$ErrorActionPreference = "Stop"

# Configuration (Edit or override via Env variables)
$GCP_PROJECT_ID = if ($env:GCP_PROJECT_ID) { $env:GCP_PROJECT_ID } else { "venturelens-499513" }
$GCP_REGION = if ($env:GCP_REGION) { $env:GCP_REGION } else { "us-central1" }
$GCS_BUCKET = if ($env:GCS_BUCKET) { $env:GCS_BUCKET } else { "venturelens-uploads-venturelens-499513" }
$FUNCTION_NAME = if ($env:FUNCTION_NAME) { $env:FUNCTION_NAME } else { "impact-archiver" }
$SCHEDULER_JOB_NAME = if ($env:SCHEDULER_JOB_NAME) { $env:SCHEDULER_JOB_NAME } else { "nightly-impact-archival" }
$ENTRY_POINT = "archive_impact"

# Service Account Names
$CF_SA_NAME = "impact-archiver-sa"
$SCHEDULER_SA_NAME = "impact-scheduler-sa"

$CF_SA_EMAIL = "${CF_SA_NAME}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"
$SCHEDULER_SA_EMAIL = "${SCHEDULER_SA_NAME}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"

Write-Host "=================================================================" -ForegroundColor Green
Write-Host " Deploying VentureLens Nightly GCS Impact Archiver" -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Green
Write-Host "Project ID:      $GCP_PROJECT_ID"
Write-Host "Region:          $GCP_REGION"
Write-Host "Bucket:          $GCS_BUCKET"
Write-Host "Function Name:   $FUNCTION_NAME"
Write-Host "Scheduler Job:   $SCHEDULER_JOB_NAME"
Write-Host "=================================================================" -ForegroundColor Green

# Set gcloud project context
Write-Host "Setting gcloud project context to: $GCP_PROJECT_ID..."
gcloud config set project $GCP_PROJECT_ID

# 1. Enable required APIs
Write-Host "Enabling required GCP APIs..."
gcloud services enable `
  cloudfunctions.googleapis.com `
  run.googleapis.com `
  cloudscheduler.googleapis.com `
  storage.googleapis.com `
  iam.googleapis.com `
  cloudbuild.googleapis.com `
  artifactregistry.googleapis.com

# Ensure Artifact Registry repo exists for Cloud Run source deploys
$prev = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
& gcloud artifacts repositories describe cloud-run-source-deploy --location=$GCP_REGION 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
  Write-Host "Creating Artifact Registry repository cloud-run-source-deploy..."
  gcloud artifacts repositories create cloud-run-source-deploy `
    --repository-format=docker `
    --location=$GCP_REGION `
    --description="Cloud Run source deploy images"
}
$ErrorActionPreference = $prev

$PROJECT_NUMBER = gcloud projects describe $GCP_PROJECT_ID --format="value(projectNumber)"
$CLOUDBUILD_SA = "${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com"
Write-Host "Granting Cloud Build SA permissions ($CLOUDBUILD_SA)..."
gcloud projects add-iam-policy-binding $GCP_PROJECT_ID `
  --member="serviceAccount:$CLOUDBUILD_SA" `
  --role="roles/artifactregistry.writer" `
  --quiet | Out-Null
gcloud projects add-iam-policy-binding $GCP_PROJECT_ID `
  --member="serviceAccount:$CLOUDBUILD_SA" `
  --role="roles/run.admin" `
  --quiet | Out-Null
gcloud projects add-iam-policy-binding $GCP_PROJECT_ID `
  --member="serviceAccount:$CLOUDBUILD_SA" `
  --role="roles/iam.serviceAccountUser" `
  --quiet | Out-Null

function Test-GcloudServiceAccountExists {
    param([string]$Email)
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & gcloud iam service-accounts describe $Email --format="value(email)" 2>$null | Out-Null
    $exists = ($LASTEXITCODE -eq 0)
    $ErrorActionPreference = $prev
    return $exists
}

function Test-GcloudSchedulerJobExists {
    param([string]$JobName, [string]$Location)
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & gcloud scheduler jobs describe $JobName --location=$Location 2>$null | Out-Null
    $exists = ($LASTEXITCODE -eq 0)
    $ErrorActionPreference = $prev
    return $exists
}

# 2. Create Cloud Function Service Account if it does not exist
if (-not (Test-GcloudServiceAccountExists -Email $CF_SA_EMAIL)) {
  Write-Host "Creating Cloud Function Service Account: $CF_SA_EMAIL..."
  gcloud iam service-accounts create $CF_SA_NAME `
    --description="Service account for VentureLens Nightly GCS Impact Archive Cloud Function" `
    --display-name="VentureLens Impact Archiver SA"
  if ($LASTEXITCODE -ne 0) { throw "Failed to create Cloud Function service account." }
} else {
  Write-Host "Cloud Function Service Account already exists."
}

# 3. Create Cloud Scheduler Service Account if it does not exist
if (-not (Test-GcloudServiceAccountExists -Email $SCHEDULER_SA_EMAIL)) {
  Write-Host "Creating Cloud Scheduler Service Account: $SCHEDULER_SA_EMAIL..."
  gcloud iam service-accounts create $SCHEDULER_SA_NAME `
    --description="Service account for Cloud Scheduler to invoke the Impact Archiver" `
    --display-name="VentureLens Impact Scheduler SA"
  if ($LASTEXITCODE -ne 0) { throw "Failed to create Scheduler service account." }
} else {
  Write-Host "Cloud Scheduler Service Account already exists."
}

# 4. Assign Storage Object Creator permission to the Function SA on the Bucket
Write-Host "Assigning storage.objects.create permission on bucket gs://$GCS_BUCKET..."
gcloud storage buckets add-iam-policy-binding "gs://$GCS_BUCKET" `
  --member="serviceAccount:$CF_SA_EMAIL" `
  --role="roles/storage.objectCreator" `
  --quiet

# 5. Deploy Cloud Run service (Dockerfile + functions-framework)
Write-Host "Deploying Cloud Run service: $FUNCTION_NAME..."
$prev = $ErrorActionPreference
$ErrorActionPreference = 'Continue'
& gcloud functions delete $FUNCTION_NAME --gen2 --region=$GCP_REGION --quiet 2>$null | Out-Null
$ErrorActionPreference = $prev

gcloud run deploy $FUNCTION_NAME `
  --source . `
  --region=$GCP_REGION `
  --service-account=$CF_SA_EMAIL `
  --no-allow-unauthenticated `
  --quiet `
  --set-env-vars="GCS_BUCKET=$GCS_BUCKET,IMPACT_API_URL=https://venturelens.app/api/v1/impact.json"
if ($LASTEXITCODE -ne 0) { throw "Cloud Run deployment failed." }

# 6. Retrieve service URL
Write-Host "Retrieving deployed service URL..."
$FUNCTION_URL = gcloud run services describe $FUNCTION_NAME --region=$GCP_REGION --format="value(status.url)"
if (-not $FUNCTION_URL) { throw "Could not resolve deployed function URL." }
Write-Host "Function URL: $FUNCTION_URL"

# 7. Grant Scheduler Service Account permissions to invoke the Cloud Run service backing the function
Write-Host "Granting invoker permissions to Scheduler Service Account..."
gcloud run services add-iam-policy-binding $FUNCTION_NAME `
  --region=$GCP_REGION `
  --member="serviceAccount:$SCHEDULER_SA_EMAIL" `
  --role="roles/run.invoker" `
  --quiet

# 8. Create or Update Cloud Scheduler job
Write-Host "Creating/Updating Cloud Scheduler Job..."
if (Test-GcloudSchedulerJobExists -JobName $SCHEDULER_JOB_NAME -Location $GCP_REGION) {
  Write-Host "Updating existing Scheduler job..."
  gcloud scheduler jobs update http $SCHEDULER_JOB_NAME `
    --schedule="0 2 * * *" `
    --uri=$FUNCTION_URL `
    --http-method=POST `
    --oidc-service-account-email=$SCHEDULER_SA_EMAIL `
    --location=$GCP_REGION `
    --time-zone="UTC"
} else {
  Write-Host "Creating new Scheduler job..."
  gcloud scheduler jobs create http $SCHEDULER_JOB_NAME `
    --schedule="0 2 * * *" `
    --uri=$FUNCTION_URL `
    --http-method=POST `
    --oidc-service-account-email=$SCHEDULER_SA_EMAIL `
    --location=$GCP_REGION `
    --time-zone="UTC"
}

Write-Host "=================================================================" -ForegroundColor Green
Write-Host " Nightly Impact Archive Deployment Completed Successfully!" -ForegroundColor Green
Write-Host "=================================================================" -ForegroundColor Green
Write-Host "Function status: ACTIVE"
Write-Host "Trigger URL:     $FUNCTION_URL"
Write-Host "Scheduler run:   Daily at 02:00 UTC"
Write-Host "To test manually, run:"
Write-Host "gcloud scheduler jobs run $SCHEDULER_JOB_NAME --location=$GCP_REGION"
Write-Host "=================================================================" -ForegroundColor Green
