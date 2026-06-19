# Gohorto profile import

Import startup profiles exported from **Gohorto** into VentureLens for Gemini screening and `/impact` KPIs.

## Export format

JSON file with top-level `profiles` array (see `data/imports/gohorto-project-profiles-2026-06-19.json` — **50 profiles** in current sample).

## Commands

### Production (venturelens.app)

```powershell
.\scripts\gohorto-production-import.ps1
# Skip redeploy if image already current:
.\scripts\gohorto-production-import.ps1 -SkipDeploy
```

Uses Cloud Run Job + `venturelens-worker` queue. Check progress:

```bash
php artisan gohorto:screening-status --watch   # local against prod DB if proxied
```

Live KPIs: https://venturelens.app/impact

### Import into Demo Incubator (visible in UI when logged in as demo)

```bash
php artisan gohorto:import data/imports/gohorto-project-profiles-2026-06-19.json --demo --dispatch-screening --delay=2
php artisan queue:work
```

`--demo` targets **Demo Incubator → summer-2026** (the cohort you see after `demo@venturelens.app` login).

### Import into separate Gohorto pilot org

```bash
php artisan gohorto:import data/imports/gohorto-project-profiles-2026-06-19.json --setup-pilot --dispatch-screening --delay=3
```

**Note:** `--setup-pilot` imports into org `gohorto-portfolio-pilot` — **not** visible on Demo Incubator. Use `--demo` for the judge/demo account UI.

### Dry run (no DB writes)

```bash
php artisan gohorto:import data/imports/gohorto-project-profiles-2026-06-19.json --setup-pilot --dry-run
```

### Import + queue screening (local)

```bash
php artisan gohorto:import data/imports/gohorto-project-profiles-2026-06-19.json --setup-pilot --dispatch-screening --delay=3
php artisan queue:work
php artisan impact:snapshot
```

### Production

Run against production DB (Cloud SQL proxy or Cloud Run job) with the same command. Ensure:

- `screenings_quota` on pilot org ≥ profile count (`--quota=600` with `--setup-pilot`)
- Queue worker is running on Cloud Run worker service
- Gemini API billing enabled

### Options

| Option | Description |
|--------|-------------|
| `--setup-pilot` | Creates org `gohorto-portfolio-pilot` + program `gohorto-portfolio-review-2026` |
| `--program=slug` | Target existing program instead |
| `--limit=N` | Import first N profiles only |
| `--offset=N` | Skip first N profiles |
| `--dispatch-screening` | Queue `ScreenApplicationJob` per import |
| `--delay=2` | Seconds between queued jobs (rate-limit Gemini) |
| `--force` | Re-import even if `gohorto_project_id` exists |
| `--dry-run` | Validate mapping only |

## Judge narrative

Frame as: **“Portfolio review pilot — AI-rescreened N real startup profiles from an incubator platform integration.”**

Related-party (Gohorto) is fine for **impact volume**; keep arms-length Stripe revenue separate on Devpost.

## Idempotency

Each import stores `form_data.integration.gohorto_project_id`. Re-running skips duplicates unless `--force`.
