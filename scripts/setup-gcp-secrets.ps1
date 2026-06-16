# Upload VentureLens secrets from local .env to GCP Secret Manager
param(
    [string]$ProjectId = $env:GCP_PROJECT_ID,
    [string]$Region = $env:GCP_REGION
)

$ErrorActionPreference = "Stop"
. "$PSScriptRoot\load-env.ps1"

if (-not $ProjectId) { $ProjectId = $env:GOOGLE_CLOUD_PROJECT_ID }
if (-not $ProjectId) {
    Write-Error "Set GCP_PROJECT_ID or GOOGLE_CLOUD_PROJECT_ID"
    exit 1
}
if (-not $Region) { $Region = "us-central1" }

function Set-GcpSecret($Name, $Value) {
    if ([string]::IsNullOrWhiteSpace($Value)) {
        Write-Warning "Skipping empty secret: $Name"
        return
    }
    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $null = gcloud secrets describe $Name --project=$ProjectId 2>$null
    $exists = $LASTEXITCODE -eq 0
    $ErrorActionPreference = $prevErrorAction
    if ($exists) {
        Write-Host "Updating secret $Name..."
        $tmp = [System.IO.Path]::GetTempFileName()
        try {
            [System.IO.File]::WriteAllText($tmp, $Value)
            gcloud secrets versions add $Name --project=$ProjectId --data-file=$tmp
        } finally {
            Remove-Item $tmp -Force -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host "Creating secret $Name..."
        $tmp = [System.IO.Path]::GetTempFileName()
        try {
            [System.IO.File]::WriteAllText($tmp, $Value)
            gcloud secrets create $Name --project=$ProjectId --replication-policy="automatic" --data-file=$tmp
        } finally {
            Remove-Item $tmp -Force -ErrorAction SilentlyContinue
        }
    }
}

Write-Host "Project: $ProjectId  Region: $Region"
gcloud config set project $ProjectId | Out-Null

# Core secrets
Set-GcpSecret "venturelens-app-key" $env:APP_KEY
Set-GcpSecret "gemini-api-key" $env:GEMINI_API_KEY
Set-GcpSecret "stripe-secret" $env:STRIPE_SECRET
Set-GcpSecret "venturelens-db-password" ($(if ($env:GCP_DB_PASSWORD) { $env:GCP_DB_PASSWORD } else { $env:DB_PASSWORD }))

if ($env:STRIPE_WEBHOOK_SECRET) {
    Set-GcpSecret "stripe-webhook-secret" $env:STRIPE_WEBHOOK_SECRET
} else {
    Write-Warning "STRIPE_WEBHOOK_SECRET empty — storing placeholder (update after Stripe webhook setup)"
    Set-GcpSecret "stripe-webhook-secret" "whsec_configure_after_deploy"
}

if ($env:STRIPE_KEY -and $env:STRIPE_KEY.StartsWith("pk_")) {
    Set-GcpSecret "stripe-key" $env:STRIPE_KEY
} else {
    Write-Warning "STRIPE_KEY is not a publishable key (pk_...) — set pk_test_ in .env before deploy for Stripe.js"
}

Write-Host ""
Write-Host "Done. Non-secret Stripe vars passed at deploy time:"
Write-Host "  STRIPE_PRICE_COHORT=$($env:STRIPE_PRICE_COHORT)"
Write-Host "  STRIPE_PRICE_STARTER=$($env:STRIPE_PRICE_STARTER)"
