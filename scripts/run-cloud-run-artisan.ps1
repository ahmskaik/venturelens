# Run a one-off Artisan command on Cloud Run (Cloud SQL + production secrets).
param(
    [Parameter(Mandatory = $true)]
    [string[]]$ArtisanArgs,
    [string]$ProjectId = $env:GCP_PROJECT_ID,
    [string]$Region = $env:GCP_REGION,
    [string]$JobName = "venturelens-artisan",
    [int]$TaskTimeout = 3600,
    [string]$Memory = "1Gi",
    [string]$Cpu = "1"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path $PSScriptRoot -Parent
Set-Location $Root
. "$PSScriptRoot\load-env.ps1"

if (-not $ProjectId) { $ProjectId = $env:GOOGLE_CLOUD_PROJECT_ID }
if (-not $ProjectId) {
    Write-Error "Set GCP_PROJECT_ID or GOOGLE_CLOUD_PROJECT_ID in .env"
    exit 1
}
if (-not $Region) { $Region = "us-central1" }

$ImageUri = "${Region}-docker.pkg.dev/${ProjectId}/venturelens/app:latest"
$SqlInstance = "${ProjectId}:${Region}:venturelens"

function Get-RagEnvVars {
    $store = if ($env:RAG_VECTOR_STORE) { $env:RAG_VECTOR_STORE.ToLower() } else { 'mysql' }
    $embedModel = if ($env:GEMINI_EMBEDDING_MODEL) { $env:GEMINI_EMBEDDING_MODEL } else { 'gemini-embedding-001' }
    $embedDims = if ($env:RAG_EMBEDDING_DIMENSIONS) { $env:RAG_EMBEDDING_DIMENSIONS } else { '768' }
    $pairs = @(
        "RAG_VECTOR_STORE=$store",
        "GEMINI_EMBEDDING_MODEL=$embedModel",
        "RAG_EMBEDDING_DIMENSIONS=$embedDims"
    )
    if ($store -eq 'qdrant' -and $env:QDRANT_URL) {
        $collection = if ($env:QDRANT_COLLECTION) { $env:QDRANT_COLLECTION } else { 'venturelens_rag' }
        $pairs += "QDRANT_URL=$($env:QDRANT_URL)"
        $pairs += "QDRANT_COLLECTION=$collection"
    }
    return ($pairs -join ",")
}

$envVars = @(
    "APP_ENV=production",
    "APP_DEBUG=false",
    "LOG_CHANNEL=stderr",
    "QUEUE_CONNECTION=database",
    "DB_CONNECTION=mysql",
    "DB_SOCKET=/cloudsql/$SqlInstance",
    "DB_DATABASE=venturelens",
    "DB_USERNAME=venturelens",
    "FILESYSTEM_UPLOADS_DISK=gcs",
    "GOOGLE_CLOUD_PROJECT_ID=$ProjectId",
    "GOOGLE_CLOUD_STORAGE_BUCKET=venturelens-uploads-$ProjectId",
    "STRIPE_PRICE_COHORT=$($env:STRIPE_PRICE_COHORT)",
    "STRIPE_PRICE_STARTER=$($env:STRIPE_PRICE_STARTER)",
    "DEMO_USER_EMAIL=$($env:DEMO_USER_EMAIL)",
    "DEMO_USER_PASSWORD=$($env:DEMO_USER_PASSWORD)",
    "GEMINI_MODEL_FLASH=$($env:GEMINI_MODEL_FLASH)",
    "GEMINI_MAX_RETRIES=$($env:GEMINI_MAX_RETRIES)",
    "GEMINI_KEY_POOL_ENABLED=$(if ($env:GEMINI_KEY_POOL_ENABLED) { $env:GEMINI_KEY_POOL_ENABLED.ToLower() } else { 'false' })",
    "GEMINI_KEY_POOL_QUOTA_COOLDOWN=$(if ($env:GEMINI_KEY_POOL_QUOTA_COOLDOWN) { $env:GEMINI_KEY_POOL_QUOTA_COOLDOWN } else { '60' })",
    (Get-RagEnvVars),
    "SESSION_DRIVER=database",
    "CACHE_STORE=database"
) -join ","

$secrets = "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest"
if ($env:GEMINI_KEY_POOL_ENABLED -eq 'true' -and $env:GEMINI_API_KEYS) {
    $secrets += ",GEMINI_API_KEYS=gemini-api-keys-pool:latest"
}

$argsJoined = ($ArtisanArgs -join ' ')
Write-Host "Cloud Run Job: php artisan $argsJoined"
Write-Host "Image: $ImageUri"

$containerArgs = @("artisan") + $ArtisanArgs
$argsCsv = ($containerArgs -join ",")

$jobArgs = @(
    "run", "jobs", "deploy", $JobName,
    "--image", $ImageUri,
    "--region", $Region,
    "--project", $ProjectId,
    "--tasks", "1",
    "--max-retries", "0",
    "--task-timeout", $TaskTimeout,
    "--memory", $Memory,
    "--cpu", $Cpu,
    "--set-cloudsql-instances", $SqlInstance,
    "--set-env-vars", $envVars,
    "--set-secrets", $secrets,
    "--command", "php",
    "--args", $argsCsv
)

& gcloud @jobArgs

if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

& gcloud run jobs execute $JobName --region $Region --project $ProjectId --wait

exit $LASTEXITCODE
