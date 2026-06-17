# VentureLens

**AI-powered startup application screening for incubators — powered by Google Gemini.**

VentureLens helps incubators, accelerators, and university innovation programs evaluate startup applications in minutes instead of weeks. Every application is screened by Gemini, scored against a configurable rubric, and turned into committee-ready reports — while AI agents run the company's own sales and support operations.

> Built for the **Build with Gemini XPRIZE** · Category: **Entrepreneurship & Job Creation**

**Project status (living doc):** [`docs/PROJECT_STATUS.md`](docs/PROJECT_STATUS.md) — what's done, what's missing, KPIs, next steps.

---

## Judge Quickstart (read this first)

### Local demo (development)

| | |
|---|---|
| **App URL** | http://127.0.0.1:8000 |
| **Impact KPIs** | http://127.0.0.1:8000/impact |
| **Impact JSON** | http://127.0.0.1:8000/api/v1/impact.json |
| **Email** | `demo@venturelens.app` |
| **Password** | `demo-password-change-me` |

### Production (after Cloud Run deploy)

| | |
|---|---|
| **App URL** | Set `APP_URL` to your Cloud Run web service URL |
| **Impact** | `{APP_URL}/impact` |

Deploy: see [`docs/commercialization/DEPLOY_CLOUD_RUN.md`](docs/commercialization/DEPLOY_CLOUD_RUN.md) and run `./scripts/deploy-cloud-run.sh deploy`.

### 5 things to click in 3 minutes

1. **Dashboard → Applications** → open any application → see Gemini score, criterion breakdown, strengths/risks.
2. Click **Replay screening** to watch a live Gemini call.
3. **AI Operations** → 6 agents in registry, % decisions by AI (L2–L3), autonomy chart, execution log.
4. **`/impact`** → live KPIs: revenue split, applications screened, founder hours saved, agent feed.
5. **Billing** → Stripe checkout (Cohort $199 / Starter $299/mo); arms-length vs related-party revenue.

### Commercialization docs

| Topic | Guide |
|-------|--------|
| Stripe checkout (judges) | [`docs/commercialization/STRIPE_JUDGE_GUIDE.md`](docs/commercialization/STRIPE_JUDGE_GUIDE.md) |
| Devpost evidence pack | [`docs/commercialization/JUDGE_EVIDENCE.md`](docs/commercialization/JUDGE_EVIDENCE.md) |
| Demo video script | [`docs/commercialization/DEMO_VIDEO_SCRIPT.md`](docs/commercialization/DEMO_VIDEO_SCRIPT.md) |
| **Devpost field-by-field copy** | [`docs/commercialization/DEVPOST_SUBMISSION.md`](docs/commercialization/DEVPOST_SUBMISSION.md) |
| Gemini API key + billing | [`docs/commercialization/GEMINI_SETUP.md`](docs/commercialization/GEMINI_SETUP.md) |
| Cloud Run deploy | [`docs/commercialization/DEPLOY_CLOUD_RUN.md`](docs/commercialization/DEPLOY_CLOUD_RUN.md) |

---

## Local setup

**Develop locally first.** Only push to GCP when you have a batch of changes ready for judges/production (see [Deploy to Google Cloud Run](#deploy-to-google-cloud-run)).

### Database (XAMPP MySQL)

1. Start **MySQL** in the XAMPP Control Panel.
2. In `.env`, use local credentials (not Cloud SQL):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=venturelens
DB_USERNAME=root
DB_PASSWORD=                    # empty for default XAMPP root

# Cloud SQL only — deploy scripts read this, not Laravel locally
GCP_DB_PASSWORD=your-cloud-sql-password
```

3. Create DB and apply schema + demo data:

```bash
# Optional: create database if missing (XAMPP)
mysql -u root -e "CREATE DATABASE IF NOT EXISTS venturelens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate:fresh --seed   # resets tables + seeds demo startups
# Or without wiping: php artisan migrate --seed
```

4. After changing `database/seeders/DatabaseSeeder.php`, re-run:

```bash
php artisan db:seed --force
# Or full reset: php artisan migrate:fresh --seed
```

### App servers

```bash
git clone <repo-url> && cd venturelens
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate --seed
npm run build                    # required after adding Vue pages

# Terminal 1 — web
php artisan serve

# Terminal 2 — queue (screening + agents; use database queue locally)
php artisan queue:work

# Terminal 3 — scheduler (Growth daily, Support hourly) OR use:
php artisan schedule:work
```

For async Gemini screening locally, set `QUEUE_CONNECTION=database` in `.env` (not `sync`).

Visit http://127.0.0.1:8000 and log in with demo credentials above.

### Stripe (test mode)

```bash
php artisan stripe:ensure-prices   # creates Cohort + Starter prices
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
# Set STRIPE_WEBHOOK_SECRET from CLI output
```

Verify: `php artisan test --filter=StripeCheckoutFlowTest`

### Trigger agents manually

```bash
php artisan agents:run-growth
php artisan agents:run-support
php artisan agents:run-finance   # backfill finance logs for existing Stripe charges
```

Then open `/ai-operations` — registry shows all 6 agents; execution log updates after jobs run.

---

## Environment variables

See `.env.example`. Critical groups:

```env
# Gemini — see docs/commercialization/GEMINI_SETUP.md
GEMINI_API_KEY=
GEMINI_MODEL_FLASH=gemini-2.5-flash
GEMINI_MAX_RETRIES=5

# Stripe — see docs/commercialization/STRIPE_JUDGE_GUIDE.md
STRIPE_KEY=pk_test_...           # publishable key
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_COHORT=price_...
STRIPE_PRICE_STARTER=price_...

# Demo (judges)
DEMO_USER_EMAIL=demo@venturelens.app
DEMO_USER_PASSWORD=demo-password-change-me
```

---

## Deploy to Google Cloud Run

**When to deploy:** After a meaningful batch of local changes (features, seeds, UI), not every small edit. Production URL: https://venturelens.app

### Manual deploy checklist (Windows)

```powershell
cd c:\xampp\htdocs\venturelens

# 1. One-time: gcloud auth + project
gcloud auth login
gcloud config set project venturelens-499513

# 2. Ensure .env has production secrets + GCP_DB_PASSWORD (Cloud SQL password)
#    Local DB_PASSWORD can stay empty for XAMPP root.

# 3. Pick what you need:

# Full first-time deploy (infra + secrets + build + web + worker)
.\scripts\deploy-cloud-run.ps1 deploy

# Typical incremental deploy (code changes only):
.\scripts\deploy-cloud-run.ps1 build    # docker build + push
.\scripts\deploy-cloud-run.ps1 web      # redeploy web service
.\scripts\deploy-cloud-run.ps1 worker   # redeploy queue worker

# After changing .env secrets (Gemini, Stripe, APP_KEY):
.\scripts\deploy-cloud-run.ps1 secrets
.\scripts\deploy-cloud-run.ps1 web
.\scripts\deploy-cloud-run.ps1 worker

# Push new seed data to production (one-off):
gcloud run deploy venturelens-web `
  --image us-central1-docker.pkg.dev/venturelens-499513/venturelens/app:latest `
  --region us-central1 --update-env-vars RUN_SEED=true
# Then remove RUN_SEED after one request:
gcloud run services update venturelens-web --region us-central1 --remove-env-vars RUN_SEED
```

| Script command | Use when |
|----------------|----------|
| `infra` | First time only — Cloud SQL, Artifact Registry, IAM |
| `secrets` | `.env` secrets changed (Gemini, Stripe, `GCP_DB_PASSWORD`) |
| `build` | Any code/frontend change |
| `web` | Redeploy HTTP service after `build` |
| `worker` | Redeploy queue worker after `build` |
| `deploy` | All of the above in one shot |

Full guide: [`docs/commercialization/DEPLOY_CLOUD_RUN.md`](docs/commercialization/DEPLOY_CLOUD_RUN.md)

### Windows (quick reference)

```powershell
$env:GCP_PROJECT_ID = "venturelens-499513"
$env:GCP_REGION = "us-central1"
.\scripts\deploy-cloud-run.ps1 build
.\scripts\deploy-cloud-run.ps1 web
.\scripts\deploy-cloud-run.ps1 worker
```

This uploads Stripe/Gemini secrets from `.env` to GCP Secret Manager when you run `secrets` or `deploy`.

### GitHub Actions secrets (for CI)

| Secret | Example |
|--------|---------|
| `STRIPE_PRICE_COHORT` | `price_1TglZY...` |
| `STRIPE_PRICE_STARTER` | `price_1TglZZ...` |
| `DEMO_USER_PASSWORD` | `demo-password-change-me` |

GCP Secret Manager: `stripe-secret`, `stripe-webhook-secret`, `gemini-api-key`, `venturelens-app-key`, `venturelens-db-password`

---

## Architecture

```
Applicant → Apply form → ScreenApplicationJob (worker) → Gemini → ScreeningResult
Growth + Support agents (scheduled) → agent_executions → /impact + AI Operations
Stripe checkout → webhook → revenue_charges (arms-length / related-party)
```

Full spec: [`docs/VENTURELENS_SYSTEM_REQUIREMENTS.md`](docs/VENTURELENS_SYSTEM_REQUIREMENTS.md)

---

## Evidence package

[`docs/evidence/`](docs/evidence/) — impact JSON snapshots, screenshots. Nightly: `php artisan impact:snapshot`.

**Judge / demo readiness**

```bash
npm run judge:smoke                                    # default: https://venturelens.app
npm run judge:smoke -- --base-url=http://127.0.0.1:8000 --out=judge-smoke-report.json
.\scripts\preflight-demo-video.ps1 -BaseUrl https://venturelens.app
```

**Live impact embed** — [`/widgets/impact/`](public/widgets/impact/) (static widget + embed snippet for partner sites). Public API: `GET /api/v1/impact.json` (CORS enabled).

**Antigravity (satellite tools)** — copy-paste master brief: [`docs/commercialization/ANTIGRAVITY_PROMPT.md`](docs/commercialization/ANTIGRAVITY_PROMPT.md)

---

## License

MIT — see [`LICENSE`](LICENSE). || https://venturelens-web-362276424525.us-central1.run.app