# Deploy latest code and import Gohorto profiles on production + snapshot KPIs when done.
param(
    [string]$ImportFile = "data/imports/gohorto-project-profiles-2026-06-19.json",
    [switch]$SkipDeploy,
    [switch]$SetupPilot,
    [int]$Limit = 0,
    [int]$DispatchDelay = 5,
    [int]$Quota = 600
)

$ErrorActionPreference = "Stop"
$Root = Split-Path $PSScriptRoot -Parent
Set-Location $Root
. "$PSScriptRoot\load-env.ps1"

if (-not (Test-Path $ImportFile)) {
    $candidate = Join-Path $Root $ImportFile
    if (-not (Test-Path $candidate)) {
        Write-Error "Import file not found: $ImportFile"
        exit 1
    }
    $ImportFile = $candidate
}

# Count profiles (PHP handles duplicate JSON keys)
$profileCount = php -r "echo count(json_decode(file_get_contents('$(($ImportFile -replace '\\','/'))'), true)['profiles'] ?? []);"
Write-Host "Import file: $ImportFile ($profileCount profiles)"
if ($profileCount -lt 1) {
    Write-Error "No profiles in import file."
    exit 1
}
if ($profileCount -lt 100) {
    Write-Warning "Export contains $profileCount profiles, not 500. Re-export from Gohorto with limit=500 for full batch."
}

if (-not $SkipDeploy) {
    Write-Host "`n=== Deploy build + web + worker ===" -ForegroundColor Cyan
    & "$PSScriptRoot\deploy-cloud-run.ps1" build
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    & "$PSScriptRoot\deploy-cloud-run.ps1" web
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    & "$PSScriptRoot\deploy-cloud-run.ps1" worker
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

$importArgs = @(
    "gohorto:import",
    $ImportFile.Replace('\', '/'),
    "--dispatch-screening",
    "--delay=$DispatchDelay",
    "--quota=$Quota"
)
if ($SetupPilot) {
    $importArgs += "--setup-pilot"
} else {
    $importArgs += "--demo"
}
if ($Limit -gt 0) {
    $importArgs += "--limit=$Limit"
}

Write-Host "`n=== Import + queue screening on production ===" -ForegroundColor Cyan
& "$PSScriptRoot\run-cloud-run-artisan.ps1" -ArtisanArgs $importArgs -JobName "venturelens-gohorto-import" -TaskTimeout 1800
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "`n=== Worker (venturelens-worker) processes the queue automatically ===" -ForegroundColor Cyan
Write-Host "Screening ~$profileCount apps may take 30-90 min. Monitor:"
Write-Host "  https://venturelens.app/applications"
Write-Host "  https://venturelens.app/ai-operations"
Write-Host "  https://venturelens.app/impact"
Write-Host ""
Write-Host "Re-snapshot repo evidence after screening (local + Cloud SQL proxy):"
Write-Host "  php artisan impact:snapshot"
