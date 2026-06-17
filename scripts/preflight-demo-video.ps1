# Pre-flight checks before recording the VentureLens demo video.
# Usage: .\scripts\preflight-demo-video.ps1 [-BaseUrl "https://..."]

param(
    [string]$BaseUrl = "https://venturelens.app"
)

$ErrorActionPreference = "Continue"
$ok = 0
$warn = 0
$fail = 0

function Pass($msg) { Write-Host "[OK]   $msg" -ForegroundColor Green; $script:ok++ }
function Warn($msg) { Write-Host "[WARN] $msg" -ForegroundColor Yellow; $script:warn++ }
function Fail($msg) { Write-Host "[FAIL] $msg" -ForegroundColor Red; $script:fail++ }

Write-Host "`nVentureLens demo video pre-flight`nBase: $BaseUrl`n" -ForegroundColor Cyan
Write-Host "Tip: cross-platform CLI also available: npm run judge:smoke -- --base-url=$BaseUrl`n" -ForegroundColor DarkGray

# Health
try {
    $health = Invoke-WebRequest -Uri "$BaseUrl/up" -UseBasicParsing -TimeoutSec 30
    if ($health.StatusCode -eq 200) { Pass "Health /up returns 200" } else { Fail "Health returned $($health.StatusCode)" }
} catch {
    Fail "Health check failed: $($_.Exception.Message)"
}

# Impact JSON
try {
    $impact = Invoke-RestMethod -Uri "$BaseUrl/api/v1/impact.json" -TimeoutSec 30
    $b = $impact.business
    $a = $impact.activity
    $ai = $impact.ai_operations
    $imp = $impact.impact

    if ($a.applications_screened -ge 1) {
        Pass "Applications screened: $($a.applications_screened)"
    } else {
        Fail "No applications screened - run replay screening before recording"
    }

    if ($a.gemini_api_calls -ge 1) {
        Pass "Gemini API calls: $($a.gemini_api_calls)"
    } else {
        Fail "No Gemini API calls logged"
    }

    if ($ai.total_agent_actions -ge 10) {
        $pct = $ai.pct_decisions_by_ai
        Pass "Agent actions: $($ai.total_agent_actions) with $pct percent at L2-L3"
    } else {
        Warn "Low agent action count: $($ai.total_agent_actions)"
    }

    if ($b.arms_length_revenue_usd -ge 600) {
        $rev = $b.arms_length_revenue_usd
        $cust = $b.arms_length_paying_customers
        Pass "Arms-length revenue: `$$rev from $cust customers"
    } elseif ($b.arms_length_revenue_usd -gt 0) {
        Warn "Arms-length revenue only `$$($b.arms_length_revenue_usd) - run verify-arms-length-checkout on production DB or Stripe test checkout"
    } else {
        Fail "Arms-length revenue is `$0 - video will fail Business Viability. Fix before recording (see DEMO_VIDEO_SCRIPT.md)"
    }

    if ($imp.accepted_startups -ge 1) {
        Pass "Accepted startups: $($imp.accepted_startups)"
    } else {
        Warn "No accepted startup - optional: accept one app on application detail before recording"
    }

    Write-Host "`nLive KPI summary:" -ForegroundColor Cyan
    Write-Host "  Revenue: arms-length `$$($b.arms_length_revenue_usd) | related `$$($b.related_party_revenue_usd)"
    Write-Host "  Screened: $($a.applications_screened) | Gemini calls: $($a.gemini_api_calls) | Founder hrs saved: $($imp.founder_hours_saved)"
    Write-Host "  Generated: $($impact.generated_at)"
} catch {
    Fail "Impact JSON failed: $($_.Exception.Message)"
}

# Auth pages
foreach ($path in @("/login", "/impact", "/widgets/impact/", "/apply/summer-2026")) {
    try {
        $r = Invoke-WebRequest -Uri "$BaseUrl$path" -UseBasicParsing -TimeoutSec 30
        if ($r.StatusCode -eq 200) { Pass "$path loads" } else { Warn "$path returned $($r.StatusCode)" }
    } catch {
        Warn "$path - $($_.Exception.Message)"
    }
}

Write-Host "`n--- Result: $ok passed, $warn warnings, $fail failures ---" -ForegroundColor Cyan
if ($fail -gt 0) {
    Write-Host "Do not record until failures are fixed.`n" -ForegroundColor Red
    exit 1
}
if ($warn -gt 0) {
    Write-Host "Recording possible with warnings - read DEMO_VIDEO_SCRIPT.md fallbacks.`n" -ForegroundColor Yellow
    exit 0
}
Write-Host "Ready to record. Open DEMO_VIDEO_SCRIPT.md teleprompter.`n" -ForegroundColor Green
exit 0
