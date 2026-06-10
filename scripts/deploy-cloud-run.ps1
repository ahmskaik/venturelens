# Deploy VentureLens to Cloud Run (Windows PowerShell)
param(
    [ValidateSet("build", "deploy", "web", "worker", "secrets")]
    [string]$Command = "deploy",
    [string]$ProjectId = $env:GCP_PROJECT_ID,
    [string]$Region = $env:GCP_REGION
)

$ErrorActionPreference = "Stop"
$Root = Split-Path $PSScriptRoot -Parent
Set-Location $Root
. "$PSScriptRoot\load-env.ps1"

if (-not $ProjectId) { $ProjectId = $env:GOOGLE_CLOUD_PROJECT_ID }
if (-not $ProjectId) {
    Write-Error "Set GCP_PROJECT_ID in environment or .env (GOOGLE_CLOUD_PROJECT_ID)"
    exit 1
}
if (-not $Region) { $Region = "us-central1" }

$ImageName = "venturelens/app"
$ServiceWeb = "venturelens-web"
$ServiceWorker = "venturelens-worker"
$ImageUri = "${Region}-docker.pkg.dev/${ProjectId}/${ImageName}:latest"
$SqlInstance = "${ProjectId}:${Region}:venturelens"

function Get-StripeEnvVars {
    $pairs = @(
        "STRIPE_PRICE_COHORT=$($env:STRIPE_PRICE_COHORT)",
        "STRIPE_PRICE_STARTER=$($env:STRIPE_PRICE_STARTER)",
        "DEMO_USER_EMAIL=$($env:DEMO_USER_EMAIL)",
        "DEMO_USER_PASSWORD=$($env:DEMO_USER_PASSWORD)",
        "GEMINI_MODEL_FLASH=$($env:GEMINI_MODEL_FLASH)",
        "GEMINI_MAX_RETRIES=$($env:GEMINI_MAX_RETRIES)",
        "SESSION_DRIVER=database",
        "CACHE_STORE=database"
    )
    if ($env:STRIPE_KEY -and $env:STRIPE_KEY.StartsWith("pk_")) {
        $pairs += "STRIPE_KEY=$($env:STRIPE_KEY)"
    }
    return ($pairs -join ",")
}

function Get-BaseEnvVars {
    return "APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,QUEUE_CONNECTION=database,DB_CONNECTION=mysql,DB_SOCKET=/cloudsql/${SqlInstance},DB_DATABASE=venturelens,DB_USERNAME=venturelens,$(Get-StripeEnvVars)"
}

function Build-Image {
    Write-Host "Building $ImageUri ..."
    gcloud auth configure-docker "${Region}-docker.pkg.dev" --quiet
    docker build -f docker/Dockerfile -t $ImageUri .
    docker push $ImageUri
}

function Deploy-Web {
    $envVars = "$(Get-BaseEnvVars),RUN_MIGRATIONS=true"
    $secrets = "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest,STRIPE_WEBHOOK_SECRET=stripe-webhook-secret:latest"
    if ($env:STRIPE_KEY -and $env:STRIPE_KEY.StartsWith("pk_")) {
        $secrets += ",STRIPE_KEY=stripe-key:latest"
    }
    gcloud run deploy $ServiceWeb `
        --image $ImageUri `
        --region $Region `
        --platform managed `
        --allow-unauthenticated `
        --port 8080 `
        --memory 1Gi `
        --cpu 1 `
        --min-instances 0 `
        --max-instances 10 `
        --add-cloudsql-instances $SqlInstance `
        --set-env-vars $envVars `
        --set-secrets $secrets
}

function Deploy-Worker {
    $envVars = "$(Get-BaseEnvVars),CONTAINER_ROLE=worker"
    gcloud run deploy $ServiceWorker `
        --image $ImageUri `
        --region $Region `
        --platform managed `
        --no-allow-unauthenticated `
        --memory 1Gi `
        --cpu 1 `
        --min-instances 1 `
        --max-instances 3 `
        --add-cloudsql-instances $SqlInstance `
        --set-env-vars $envVars `
        --set-secrets "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest"
}

switch ($Command) {
    "secrets" { & "$PSScriptRoot\setup-gcp-secrets.ps1" -ProjectId $ProjectId -Region $Region }
    "build"   { Build-Image }
    "web"     { Deploy-Web }
    "worker"  { Deploy-Worker }
    "deploy"  {
        & "$PSScriptRoot\setup-gcp-secrets.ps1" -ProjectId $ProjectId -Region $Region
        Build-Image
        Deploy-Web
        Deploy-Worker
        $url = gcloud run services describe $ServiceWeb --region $Region --format="value(status.url)"
        Write-Host ""
        Write-Host "Deployed. Web URL: $url"
        Write-Host "Next: set APP_URL=$url on web service, register Stripe webhook at $url/stripe/webhook"
    }
}
