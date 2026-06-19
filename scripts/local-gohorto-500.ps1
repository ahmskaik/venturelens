# Ensure local DB has demo user, summer-2026 cohort, and all 500 Gohorto profiles.
$ErrorActionPreference = "Stop"
$Root = Split-Path $PSScriptRoot -Parent
Set-Location $Root

$ImportFile = "data/imports/gohorto-project-profiles-2026-06-19-500.json"
if (-not (Test-Path $ImportFile)) {
    Write-Error 'Missing import file — copy the Gohorto export into data/imports/'
    exit 1
}

Write-Host "=== Seeding demo org + cohort ===" -ForegroundColor Cyan
php artisan db:seed --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "`n=== Importing 500 Gohorto profiles (skip existing) ===" -ForegroundColor Cyan
php artisan gohorto:import $ImportFile --demo --quota=650
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "`n=== Verifying ===" -ForegroundColor Cyan
php scripts/verify-gohorto-import.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host 'Local ready: http://127.0.0.1:8000/applications — login demo@venturelens.app' -ForegroundColor Green
