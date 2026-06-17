# Deploy VentureLens to Cloud Run (Windows PowerShell)
param(
    [ValidateSet("build", "deploy", "web", "worker", "secrets", "infra")]
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

function Get-RagEnvVars {
    $store = if ($env:RAG_VECTOR_STORE) { $env:RAG_VECTOR_STORE.ToLower() } else { 'mysql' }
    if ($store -notin @('mysql', 'qdrant')) {
        Write-Warning "Invalid RAG_VECTOR_STORE=$store — using mysql"
        $store = 'mysql'
    }

    $embedModel = if ($env:GEMINI_EMBEDDING_MODEL) { $env:GEMINI_EMBEDDING_MODEL } else { 'gemini-embedding-001' }
    $embedDims = if ($env:RAG_EMBEDDING_DIMENSIONS) { $env:RAG_EMBEDDING_DIMENSIONS } else { '768' }

    $pairs = @(
        "RAG_VECTOR_STORE=$store",
        "GEMINI_EMBEDDING_MODEL=$embedModel",
        "RAG_EMBEDDING_DIMENSIONS=$embedDims"
    )

    if ($store -eq 'qdrant') {
        if (-not $env:QDRANT_URL) {
            Write-Error "RAG_VECTOR_STORE=qdrant requires QDRANT_URL in .env before deploy"
            exit 1
        }
        $collection = if ($env:QDRANT_COLLECTION) { $env:QDRANT_COLLECTION } else { 'venturelens_rag' }
        $pairs += "QDRANT_URL=$($env:QDRANT_URL)"
        $pairs += "QDRANT_COLLECTION=$collection"
    }

    return ($pairs -join ",")
}

function Get-RagSecrets {
    $store = if ($env:RAG_VECTOR_STORE) { $env:RAG_VECTOR_STORE.ToLower() } else { 'mysql' }
    if ($store -eq 'qdrant' -and $env:QDRANT_API_KEY) {
        return "QDRANT_API_KEY=qdrant-api-key:latest"
    }
    return ""
}

function Get-StripeEnvVars {
    $pairs = @(
        "STRIPE_PRICE_COHORT=$($env:STRIPE_PRICE_COHORT)",
        "STRIPE_PRICE_STARTER=$($env:STRIPE_PRICE_STARTER)",
        "DEMO_USER_EMAIL=$($env:DEMO_USER_EMAIL)",
        "DEMO_USER_PASSWORD=$($env:DEMO_USER_PASSWORD)",
        "GEMINI_MODEL_FLASH=$($env:GEMINI_MODEL_FLASH)",
        "GEMINI_MAX_RETRIES=$($env:GEMINI_MAX_RETRIES)",
        (Get-RagEnvVars),
        "SESSION_DRIVER=database",
        "CACHE_STORE=database"
    )
    if ($env:STRIPE_KEY -and $env:STRIPE_KEY.StartsWith("pk_")) {
        $pairs += "STRIPE_KEY=$($env:STRIPE_KEY)"
    }
    return ($pairs -join ",")
}

function Get-BaseEnvVars {
    return "APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,QUEUE_CONNECTION=database,DB_CONNECTION=mysql,DB_SOCKET=/cloudsql/${SqlInstance},DB_DATABASE=venturelens,DB_USERNAME=venturelens,FILESYSTEM_UPLOADS_DISK=gcs,GOOGLE_CLOUD_PROJECT_ID=${ProjectId},GOOGLE_CLOUD_STORAGE_BUCKET=venturelens-uploads-${ProjectId},$(Get-StripeEnvVars)"
}

function Set-WebAppUrl($Url) {
    Write-Host "Setting APP_URL=$Url on $ServiceWeb ..."
    gcloud run services update $ServiceWeb `
        --region $Region `
        --update-env-vars "APP_URL=$Url" `
        --quiet
}

function Test-DeployedHealth($Url) {
    $healthUrl = "$Url/up"
    Write-Host "Health check: $healthUrl"
    try {
        $resp = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 30
        if ($resp.StatusCode -eq 200) {
            Write-Host "  OK ($($resp.StatusCode))"
        } else {
            Write-Warning "  Unexpected status: $($resp.StatusCode)"
        }
    } catch {
        Write-Warning "  Health check failed (service may still be starting): $_"
    }
}

function Build-Image {
    Write-Host "Building $ImageUri ..."
    gcloud auth configure-docker "${Region}-docker.pkg.dev" --quiet
    docker build -f docker/Dockerfile -t $ImageUri .
    docker push $ImageUri
}

function Deploy-Web {
    $envVars = "$(Get-BaseEnvVars),RUN_MIGRATIONS=true,RUN_SEED=true"
    $secrets = "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest,STRIPE_WEBHOOK_SECRET=stripe-webhook-secret:latest"
    $ragSecrets = Get-RagSecrets
    if ($ragSecrets) { $secrets += ",$ragSecrets" }
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
    $secrets = "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest"
    $ragSecrets = Get-RagSecrets
    if ($ragSecrets) { $secrets += ",$ragSecrets" }
    gcloud run deploy $ServiceWorker `
        --image $ImageUri `
        --region $Region `
        --platform managed `
        --no-allow-unauthenticated `
        --no-cpu-throttling `
        --memory 1Gi `
        --cpu 1 `
        --min-instances 1 `
        --max-instances 3 `
        --add-cloudsql-instances $SqlInstance `
        --set-env-vars $envVars `
        --set-secrets $secrets
}

switch ($Command) {
    "infra"   { & "$PSScriptRoot\setup-gcp-infra.ps1" -ProjectId $ProjectId -Region $Region }
    "secrets" { & "$PSScriptRoot\setup-gcp-secrets.ps1" -ProjectId $ProjectId -Region $Region }
    "build"   { Build-Image }
    "web"     { Deploy-Web }
    "worker"  { Deploy-Worker }
    "deploy"  {
        & "$PSScriptRoot\setup-gcp-infra.ps1" -ProjectId $ProjectId -Region $Region
        & "$PSScriptRoot\setup-gcp-secrets.ps1" -ProjectId $ProjectId -Region $Region
        Build-Image
        Deploy-Web
        Deploy-Worker
        $customUrl = $env:APP_URL
        if ($customUrl -match '^https?://[^#/\s]+') {
            $customUrl = $Matches[0].TrimEnd('/')
        } elseif ($env:CUSTOM_DOMAIN) {
            $customUrl = "https://$($env:CUSTOM_DOMAIN.TrimEnd('/'))"
        } else {
            $customUrl = gcloud run services describe $ServiceWeb --region $Region --format="value(status.url)"
        }
        Set-WebAppUrl $customUrl
        $url = $customUrl
        Write-Host ""
        Write-Host "Deployed. Web URL: $url"
        Write-Host "  Impact:   $url/impact"
        Write-Host "  Health:   $url/up"
        Write-Host "  Demo:     demo@venturelens.app / $($env:DEMO_USER_PASSWORD)"
        Write-Host ""
        Write-Host "Stripe webhook (optional): $url/stripe/webhook"
        Test-DeployedHealth $url
    }
}
