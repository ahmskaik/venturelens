# VentureLens

**AI-powered startup application screening for incubators — powered by Google Gemini.**

VentureLens helps incubators, accelerators, and university innovation programs evaluate startup applications in minutes instead of weeks. Every application is screened by Gemini, scored against a configurable rubric, and turned into committee-ready reports — while AI agents run the company's own sales, onboarding, support, and finance operations.

> Built for the **Build with Gemini XPRIZE** · Category: **Entrepreneurship & Job Creation**

---

## 🏆 Judge Quickstart (read this first)

**Live demo:** https://venturelens.app  
**Impact dashboard (live KPIs):** https://venturelens.app/impact

**Demo login (Program Manager):**
```
Email:    demo@venturelens.app
Password: <set DEMO_USER_PASSWORD; document here before submission>
```

**See the whole product in under 3 minutes — click these 5 things:**

1. **Applications** → open any application → see the **live Gemini score**, criterion breakdown, strengths/risks, and the raw AI reasoning.
2. Click **"Replay screening"** on an application to watch a **Gemini call run live** in production.
3. **AI Operations** dashboard → see the **6 autonomous agents** (Growth, Onboarding, Support, Screening, Finance, Success), the **% of business decisions executed by AI**, and the **autonomy distribution (L0–L3)**.
4. **/impact** → live category-impact KPIs: applications screened, founder hours saved, programs enabled, countries reached, jobs influenced.
5. **Billing** → real Stripe subscriptions; revenue split into **arms-length vs related-party**.

**Why this matters:** AI is not a feature inside VentureLens — **AI operates the VentureLens business**. Humans set strategy and approve high-stakes actions; Gemini agents do the rest.

---

## Hackathon compliance

| Requirement | How VentureLens meets it |
|-------------|--------------------------|
| **Gemini API** | ≥1 Gemini call per application in production; token usage logged. No non-Google LLMs in the deployed app. |
| **Google Cloud** | Runs on Cloud Run + Cloud SQL + Cloud Storage + Cloud Logging. |
| **AI transforms workflows** | 6 production agents run screening, sales, onboarding, support, finance, and success. |
| **New project** | Created after 2026-05-19. See *Pre-existing work disclosure* below. |
| **Real business** | Live Stripe revenue from arms-length customers; users across multiple programs. |

### Pre-existing work disclosure
VentureLens is a **new product** (new brand, codebase, customers) built during the hackathon window by a team with prior incubator domain expertise from **Gohorto** (E-Incubation platform) and pilot support from **BINA Business Incubator**. Standard open-source frameworks (Laravel, Vue) were used as boilerplate; all Gemini integration, screening logic, autonomous agents, and product code are original work from this build window. Revenue from related parties (BINA/Gohorto/existing relationships) is reported **separately** from arms-length revenue.

---

## Tech stack

- **Backend:** PHP 8.2+, Laravel 11+
- **Frontend:** Vue 3 + Inertia.js + Tailwind CSS
- **AI:** Google Gemini API (`gemini-2.0-flash`, `gemini-2.5-pro`)
- **Infra:** Google Cloud Run (web + worker), Cloud SQL (MySQL), Cloud Storage, Cloud Logging
- **Billing:** Stripe (Laravel Cashier)
- **Queue/cache:** Redis

---

## Local setup

```bash
git clone <repo-url> && cd venturelens
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed     # seeds demo org, applications, and agent activity
npm run build
php artisan serve              # web
php artisan queue:work         # worker (screening + agents)
```

Visit `http://localhost:8000`. Log in with the demo credentials above.

---

## Environment variables

See `.env.example`. Key groups:

```env
APP_NAME=VentureLens
APP_URL=https://venturelens.app

# Gemini
GEMINI_API_KEY=
GEMINI_MODEL_FLASH=gemini-2.0-flash
GEMINI_MODEL_PRO=gemini-2.5-pro

# Google Cloud
GOOGLE_CLOUD_PROJECT_ID=
GOOGLE_CLOUD_STORAGE_BUCKET=
GOOGLE_APPLICATION_CREDENTIALS=/secrets/gcp-key.json

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_COHORT=
STRIPE_PRICE_STARTER=
STRIPE_PRICE_PRO=

# Demo (judges)
DEMO_USER_EMAIL=demo@venturelens.app
DEMO_USER_PASSWORD=
```

---

## Deploy to Google Cloud Run

```bash
# Build & push
gcloud builds submit --tag <REGION>-docker.pkg.dev/$PROJECT/venturelens/app

# Deploy web
gcloud run deploy venturelens-web \
  --image <REGION>-docker.pkg.dev/$PROJECT/venturelens/app \
  --add-cloudsql-instances $PROJECT:$REGION:venturelens \
  --set-secrets GEMINI_API_KEY=gemini-api-key:latest,STRIPE_SECRET=stripe-secret:latest \
  --allow-unauthenticated --region <REGION>

# Deploy worker (queue + agents)
gcloud run deploy venturelens-worker \
  --image <REGION>-docker.pkg.dev/$PROJECT/venturelens/app \
  --command "php" --args "artisan,queue:work" \
  --no-allow-unauthenticated --region <REGION>
```

Cloud Scheduler triggers the autonomous agents on a cadence (see `routes/console.php`).

---

## Architecture

```
Applicant → Apply form → API (Cloud Run) → ScreenApplicationJob (worker)
   → GeminiClient (screen) → ScreeningResult (Cloud SQL) → Program Manager review
Autonomous agents (Growth, Onboarding, Support, Finance, Success) run on schedule/events,
call Gemini, and log every decision to agent_executions → surfaced on /impact + AI Operations.
```

Full design: [`docs/VENTURELENS_SYSTEM_REQUIREMENTS.md`](docs/VENTURELENS_SYSTEM_REQUIREMENTS.md).

---

## Evidence package

Production proof for judges lives in [`docs/evidence/`](docs/evidence/): AI Operations dashboard, Gemini API logs, screening demo, `/impact` snapshot, and revenue export.

---

## License

MIT — see [`LICENSE`](LICENSE).
