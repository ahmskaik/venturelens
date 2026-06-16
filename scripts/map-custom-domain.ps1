# Map a custom domain (e.g. venturelens.app) to Cloud Run venturelens-web
param(
    [ValidateSet("verify", "map", "dns", "status", "apply")]
    [string]$Command = "apply",
    [string]$Domain = "venturelens.app",
    [switch]$IncludeWww,
    [string]$ProjectId = $env:GCP_PROJECT_ID,
    [string]$Region = $env:GCP_REGION,
    [string]$Service = "venturelens-web"
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

$Gcloud = $env:GCP_GCLOUD
if (-not $Gcloud) {
    $candidates = @(
        "$env:LOCALAPPDATA\Google\Cloud SDK\google-cloud-sdk\bin\gcloud.cmd",
        "$env:ProgramFiles\Google\Cloud SDK\google-cloud-sdk\bin\gcloud.cmd"
    )
    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            $Gcloud = $candidate
            break
        }
    }
}
if (-not $Gcloud) {
    $Gcloud = "gcloud"
}

function Invoke-Gcloud {
    param([string[]]$GcloudArgs)
    & $Gcloud @GcloudArgs
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

function Test-VerifiedDomain([string]$Name) {
    $verified = & $Gcloud domains list-user-verified --format="value(ID)" 2>$null
    return ($verified -contains $Name)
}

function Show-VerificationHelp([string]$Name) {
    Write-Host ""
    Write-Host "Domain '$Name' is not verified for ahmskaik@gmail.com in this GCP project."
    Write-Host ""
    Write-Host "1. Run:  .\scripts\map-custom-domain.ps1 verify"
    Write-Host "2. In Google Search Console, choose DNS TXT verification."
    Write-Host "3. At your registrar (Connaxis), add the TXT record on the root (@)."
    Write-Host "4. Wait a few minutes, click Verify in Search Console."
    Write-Host "5. Re-run:  .\scripts\map-custom-domain.ps1 apply"
    Write-Host ""
}

function New-DomainMapping([string]$Name) {
    Write-Host "Creating domain mapping: $Name -> $Service ($Region) ..."
    $prev = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $out = & $Gcloud beta run domain-mappings create `
        --service $Service `
        --domain $Name `
        --region $Region `
        --project $ProjectId 2>&1
    $ErrorActionPreference = $prev
    if ($LASTEXITCODE -ne 0) {
        $text = $out | Out-String
        if ($text -match "does not appear to be verified") {
            Show-VerificationHelp $Name
            exit 1
        }
        if ($text -match "already exists") {
            Write-Host "  Mapping already exists for $Name"
            return
        }
        Write-Error $text
        exit 1
    }
    Write-Host "  Created mapping for $Name"
}

function Show-DnsRecords([string]$Name) {
    Write-Host ""
    Write-Host "DNS records required for $Name (add at Connaxis / your registrar):"
    Write-Host ""
    $prev = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $out = & $Gcloud beta run domain-mappings describe `
        --domain $Name `
        --region $Region `
        --project $ProjectId `
        --format "table(status.resourceRecords.type,status.resourceRecords.name,status.resourceRecords.rrdata)" 2>&1
    $ErrorActionPreference = $prev
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  (no mapping yet — run .\scripts\map-custom-domain.ps1 apply after domain verification)"
        return
    }
    $out | Write-Host
    Write-Host ""
    Write-Host "Remove any parking-page A/CNAME records that conflict with the records above."
    Write-Host "Propagation can take 15-60 minutes. SSL cert provisioning may take up to 24h."
}

function Set-ProductionAppUrl([string]$Url) {
    if (-not $Url.StartsWith("https://")) {
        $Url = "https://$Url"
    }
    $Url = $Url.TrimEnd("/")
    Write-Host "Setting APP_URL=$Url on $Service ..."
    Invoke-Gcloud @(
        "run", "services", "update", $Service,
        "--region", $Region,
        "--project", $ProjectId,
        "--update-env-vars", "APP_URL=$Url",
        "--quiet"
    )
}

function Test-CustomDomainHealth([string]$Url) {
    if (-not $Url.StartsWith("https://")) {
        $Url = "https://$Url"
    }
    $healthUrl = "$Url/up"
    Write-Host "Health check: $healthUrl"
    try {
        $resp = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 30
        Write-Host "  OK ($($resp.StatusCode))"
    } catch {
        Write-Warning "  Not reachable yet (DNS or SSL may still be propagating): $_"
    }
}

Invoke-Gcloud @("config", "set", "project", $ProjectId) | Out-Null

switch ($Command) {
    "verify" {
        Write-Host "Opening Google Search Console to verify $Domain ..."
        Invoke-Gcloud @("domains", "verify", $Domain)
        Show-VerificationHelp $Domain
    }
    "map" {
        if (-not (Test-VerifiedDomain $Domain)) {
            Show-VerificationHelp $Domain
            exit 1
        }
        New-DomainMapping $Domain
        if ($IncludeWww) {
            New-DomainMapping "www.$Domain"
        }
    }
    "dns" {
        Show-DnsRecords $Domain
        if ($IncludeWww) {
            Show-DnsRecords "www.$Domain"
        }
    }
    "status" {
        Write-Host "Verified domains:"
        Invoke-Gcloud @("domains", "list-user-verified")
        Write-Host ""
        Write-Host "Domain mappings in ${Region}:"
        Invoke-Gcloud @("beta", "run", "domain-mappings", "list", "--region", $Region, "--project", $ProjectId)
        Show-DnsRecords $Domain
        Test-CustomDomainHealth $Domain
    }
    "apply" {
        if (-not (Test-VerifiedDomain $Domain)) {
            Write-Host "Step 1: verify domain ownership first."
            Show-VerificationHelp $Domain
            exit 1
        }
        New-DomainMapping $Domain
        if ($IncludeWww) {
            New-DomainMapping "www.$Domain"
        }
        Show-DnsRecords $Domain
        if ($IncludeWww) {
            Show-DnsRecords "www.$Domain"
        }
        Set-ProductionAppUrl $Domain
        Write-Host ""
        Write-Host "Done. After DNS propagates, your app will be at: https://$Domain"
        Write-Host "  Impact:   https://$Domain/impact"
        Write-Host "  AI ops:   https://$Domain/ai-operations"
        Write-Host ""
        Write-Host "Update Stripe webhook to: https://$Domain/stripe/webhook"
        Test-CustomDomainHealth $Domain
    }
}
