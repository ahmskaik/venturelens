# VentureLens

**AI-powered startup application screening for incubators — powered by Google Gemini.**

VentureLens helps incubators, accelerators, and university innovation programs evaluate startup applications in minutes instead of weeks. Every application is screened by Gemini, scored against a configurable rubric, and turned into committee-ready reports — while AI agents run the company's own sales and support operations.

> Built for the **Build with Gemini XPRIZE** · Category: **Entrepreneurship & Job Creation**

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
| Gemini API key + billing | [`docs/commercialization/GEMINI_SETUP.md`](docs/commercialization/GEMINI_SETUP.md) |
| Cloud Run deploy | [`docs/commercialization/DEPLOY_CLOUD_RUN.md`](docs/commercialization/DEPLOY_CLOUD_RUN.md) |

---

## Local setup

```bash
git clone <repo-url> && cd venturelens
cp .env.example .env
composer install && npm install
php artisan key:generate
php artisan migrate --seed
npm run build                    # required after adding Vue pages

# Terminal 1 — web
php artisan serve

# Terminal 2 — queue (screening + agents)
php artisan queue:work

# Terminal 3 — scheduler (Growth daily, Support hourly) OR use:
php artisan schedule:work
```

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

### Windows (recommended)

```powershell
$env:GCP_PROJECT_ID = "your-gcp-project-id"
$env:GCP_REGION = "us-central1"
# Ensure .env has STRIPE_SECRET, STRIPE_PRICE_*, GEMINI_API_KEY, DB_PASSWORD
.\scripts\deploy-cloud-run.ps1 deploy
```

This uploads Stripe/Gemini secrets from `.env` to GCP Secret Manager, then deploys **web + worker** with price IDs as env vars.

Full guide: [`docs/commercialization/DEPLOY_CLOUD_RUN.md`](docs/commercialization/DEPLOY_CLOUD_RUN.md)

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

---

## License

MIT — see [`LICENSE`](LICENSE).
