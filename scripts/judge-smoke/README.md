# VentureLens judge smoke test

Cross-platform CLI for judges, CI, and pre-demo checks against a live VentureLens deployment.

## Usage

```bash
# Production (default)
npm run judge:smoke

# Custom URL + JSON report
node scripts/judge-smoke/smoke.mjs --base-url=http://127.0.0.1:8000 --out=judge-smoke-report.json

# Environment variable
VENTURELENS_BASE_URL=https://venturelens.app node scripts/judge-smoke/smoke.mjs
```

## Checks

| Check | Fail if |
|-------|---------|
| `GET /up` | Not HTTP 200 |
| `GET /api/v1/impact.json` | Invalid JSON or missing KPI fields |
| Applications screened | 0 |
| Gemini API calls | 0 |
| Arms-length revenue | $0 (warn if &lt; $600) |
| Agent actions | &lt; 10 (warning) |
| Public pages | `/login`, `/impact`, `/widgets/impact/`, apply form |

Exit code **0** = no failures (warnings allowed). **1** = at least one failure.

## PowerShell pre-flight

`scripts/preflight-demo-video.ps1` runs the same checks in PowerShell. Use either tool before recording the demo video.
