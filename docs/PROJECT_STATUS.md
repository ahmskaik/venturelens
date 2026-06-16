# VentureLens — Project Status

**Last updated:** 2026-06-11  
**Competition:** [Build with Gemini XPRIZE](https://www.geminixprize.com/) · Category: **Entrepreneurship & Job Creation**  
**Devpost:** Project **VentureLens** (draft in progress) · Submission deadline: **Aug 17, 2026, 1:00 PM PT**  
**Phase:** **Advanced stage push** — see [`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md) for mandatory path  
**Advanced gate:** A 🟡 · B 🟢 · C 🟡 · D 🔴 · E 🟡 · F 🔴 — **not advanced-ready until all green**

> **Living doc:** Agents and humans **must** update this file after every milestone (see protocol below). **Mandatory scope:** [`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md) — do not build outside it until gate is green. Spec: [`VENTURELENS_SYSTEM_REQUIREMENTS.md`](VENTURELENS_SYSTEM_REQUIREMENTS.md).

---

## Milestone update protocol (required)

Update **`docs/PROJECT_STATUS.md`** at the **end of any session** that completes one of these:

| Trigger | What to update |
|---------|----------------|
| Feature shipped or agent goes live | Move row in **Implemented** / **Partially done** / **Not implemented** |
| Stripe payment or revenue change | **Live KPIs**, **Executive summary**, **Devpost revenue table**, run `php artisan impact:snapshot` |
| Evidence / Devpost step done | **Submission checklist**, **Devpost & registration** |
| Deferral or scope change | **Explicitly deferred**, **Next actions** |
| Bug fix affecting judges/demo | **Where we are now**, **Changelog** |
| Deploy / infra change | **Infrastructure**, **Executive summary** GCP row |

**Every update must:**

1. Set **Last updated** to today (`YYYY-MM-DD`).
2. Add one row to **Changelog** (date + one-line summary).
3. Refresh **Live KPIs** from latest `docs/evidence/impact-YYYYMMDD.json` if metrics changed.
4. Adjust **Next actions** — remove completed items, keep top 3–7 ordered.
5. Update status emojis in **Executive summary** / **Submission checklist** (✅ 🟡 ⬜ 🔴).

**Quick refresh after KPI change:**

```bash
php artisan impact:snapshot
# Then copy business.* and activity.* from docs/evidence/impact-YYYYMMDD.json into Live KPIs table
```

**Do not** mark Devpost or GCP items ✅ unless actually done. Keep related-party and arms-length figures separate.

---

## Executive summary

VentureLens is an **AI-native B2B SaaS** that screens startup applications for incubators using **Google Gemini** in production. Built as a **new product** (post–May 19, 2026) by a team with incubator domain expertise from **Gohorto** and pilot context from **BINA Business Incubator** (Turkey).

**Thesis for judges:** *VentureLens is an AI-operated company that makes AI-powered startup selection accessible to every incubator — and it earns real money doing it.*

| Judging criterion | Status | Evidence |
|-------------------|--------|----------|
| **Business Viability** | 🟢 Strong | **$697** arms-length + **$199** related-party (Stripe test); **3** arms-length customers |
| **AI-Native Operations** | 🟢 Strong | **6 live agents**; Onboarding on signup, Success on payment, committee decisions |
| **Category Impact** | 🟢 Improved | `impact-20260611.json` — 7 screened, 1 accepted, 3 jobs modeled |
| **Google Cloud (rules)** | 🟡 Ready to deploy | Scripts + Dockerfile ready; run `deploy-cloud-run.ps1 deploy` |
| **Devpost submission** | 🟡 In progress | Draft on Devpost; video + final submit pending |

**Advanced-ready checklist:** [`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md) — 6 gates (A–F). Currently **3/6 green** (viability strong, impact improved, AI-native strong).

---

## What is necessary (decision summary)

Based on Build with Gemini XPRIZE judging, these are **required** for advanced positioning — everything else is cut until the gate is green.

| Priority | Necessary | Why |
|----------|-----------|-----|
| **P0** | GCP production deploy (Cloud Run) | 🟡 Scripts ready — install gcloud + Docker, set `GCP_PROJECT_ID` in `.env` |
| **P0** | 6 **live** agents (add Onboarding + Success) | ✅ **Done** — Sprint 1 shipped |
| **P0** | Activity KPIs on `/impact` (screened, Gemini calls, founder hours) | ✅ **Done** — `impact-20260611.json` |
| **P0** | Decision workflow + ≥1 accepted startup | ✅ **Done** — app #1 accepted |
| **P0** | Arms-length revenue **≥ $600** (3rd customer) | ✅ **Done** — Pacific Innovation Lab ($199) via `verify-arms-length-checkout.php` |
| **P0** | Verifiable testimonial (public URL) | Credibility — Success agent drafts request on payment |
| **P0** | Evidence pack (PDF + 5 PNGs + video + Devpost submit) | Finals / top tier |
| **P0** | GitHub shared with judges | Rules |
| **P1** | Gemini-drafted founder email (approve → send) | ✅ **Done** — on decision, approve on app detail |
| **Cut** | Pro tier, PDF export, i18n, mentor matching, rubrics CRUD | Post-gate only |

Full gate definitions: **[`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md)**

---

## Where we are now

VentureLens has a **working core loop**, **Stripe billing**, **`/impact` with live KPIs**, **six agents**, **founder portal**, **Sprint 2 KPI boost**, and **3rd arms-length customer** ($697 / 3 customers). Evidence pack largely complete (4 PNGs + revenue JSON/HTML/PDF). Next: testimonial URL, GCP deploy, demo video, Devpost final submit.

---

## Sprint 1 status ([`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md))

| # | Item | Status | Proof |
|---|------|--------|-------|
| 1 | **OnboardingAgent (A2)** on registration | ✅ | `RunOnboardingAgentJob` from `RegisteredUserController` · log `program_setup` L2 |
| 2 | **Decision workflow** accept / reject / shortlist | ✅ | `POST /applications/{id}/decision` · `committee_decision` L2 · UI on app detail |
| 3 | **SuccessAgent (A6)** after `RevenueCharge` | ✅ | `BillingService` → `SuccessOutreachDraft` · log `testimonial_request_drafted` L1 |

**Tests (2026-06-10):** `OnboardingAgentTest`, `RegistrationOnboardingTest`, `ApplicationDecisionFlowTest`, `SuccessAgentTest`, `FinanceAgentTest` — all passing.

---

Source: [`docs/evidence/impact-20260611.json`](evidence/impact-20260611.json) · Refresh: `php artisan impact:snapshot`

| KPI | Current | Scorecard floor | Target (competitive) |
|-----|---------|-----------------|----------------------|
| Arms-length revenue (USD) | **697** ✅ | 600 | 4,000 |
| Arms-length paying customers | **3** ✅ | 3 | 8 |
| Related-party revenue (USD) | **199** | (report separately) | — |
| Total revenue (USD) | **896** | — | — |
| Applications screened | **7** ✅ | 100 | 1,000 |
| Gemini API calls | **7** ✅ | 500 | 5,000 |
| Founder hours saved | **5.3** ✅ | — | — |
| Accepted startups | **1** ✅ | 1 | — |
| Jobs influenced (modeled) | **3** ✅ | > 0 | — |
| % decisions by AI | **88.6%** ✅ | 50% | 75% |
| Registered organizations | **7** | 5 | 25 |
| Programs enabled | **4** | — | — |
| Countries reached | **4** | 1 | 5+ |
| Agent actions (total) | **79** | — | — |
| Subscription renewals | **0** | — | 1+ |

**Verify in browser:** `/impact` and `/applications/1` (status: accepted).

---

## Devpost & registration

| Item | Status |
|------|--------|
| Hackathon registration | ✅ Registered on Devpost |
| Project page | ✅ VentureLens (draft) |
| Category | Entrepreneurship & Job Creation |
| Project overview + story | 🟡 Draft content prepared |
| Additional info (revenue, agents, GCP) | 🟡 Partially filled |
| Image gallery (4–6 screenshots) | ✅ 4 PNGs in `docs/evidence/` (impact, billing, AI ops, application screening) |
| Demo video (< 3 min) | ⬜ Todo |
| Final submit | ⬜ Due Aug 17, 2026 (target Aug 15 buffer) |
| GitHub shared with judges | ⬜ Confirm `testing@devpost.com`, `judging@hacker.fund` |

**Revenue reporting (Devpost form):**

| Field | Value (test mode, as of snapshot) |
|-------|-----------------------------------|
| Total arms-length revenue | $697 |
| Related-party revenue | $199 (report separately) |
| May 2026 | $0 |
| June 2026 | $697 arms-length + $199 related-party (adjust by month from Stripe) |

Use **personal Gmail** orgs for arms-length; **demo@venturelens.app** / Gohorto/BINA domains = related-party per `RevenueClassifier`.

---

## Implemented ✅

### Core product (P0)

| Feature | Location / notes |
|---------|------------------|
| Auth (login, register, org on signup) | `RegisteredUserController`, default rubric created |
| Public apply form + PDF upload | `/apply/{slug}` · local disk (`FILESYSTEM_UPLOADS_DISK=local`) |
| Gemini screening pipeline | `ScreenApplicationJob`, `GeminiScreeningService`, `GeminiClient` (429 retry) |
| Agent execution logging | `AgentExecutionLogger` · every screening step |
| Admin applications list + detail | `/programs/{program}/applications`, `/applications/{id}` |
| Replay / rescreen | `POST /applications/{id}/rescreen` |
| **Committee decision workflow** | `POST /applications/{id}/decision` · accept/reject/shortlist/waitlist · L2 audit log |
| **Founder email (Gemini draft → approve → send)** | `FounderCommunicationService` · `POST .../communications/{id}/send` |
| Dashboard (org stats, usage) | `/dashboard` |
| Health check | `GET /health` |

### Billing & revenue (P0 🏆)

| Feature | Location / notes |
|---------|------------------|
| Stripe Cohort checkout ($199 one-time) | `BillingController` · `mode: payment` |
| Stripe Starter subscription ($299/mo) | `newSubscription()->checkout()` |
| Inertia → Stripe redirect fix | `Inertia::location()` + form POST in `Billing/Index.vue` |
| Webhook fulfillment | `StripeWebhookController` |
| Revenue classifier (arms-length / related-party) | `RevenueClassifier` · `RELATED_PARTY_*` in `.env` |
| Revenue charges + plan quotas | `BillingService`, `revenue_charges` table |
| Billing UI + success page | `/billing`, `/billing/success` |
| Stripe Link disabled on checkout | `wallet_options.link.display = never` |
| Finance Agent (L3) on every charge | `FinanceAgent` · `stripe_reconcile` in `agent_executions` |
| Backfill finance logs | `php artisan agents:run-finance` |

### AI-native operations (P0 🏆)

| Agent | Status | Trigger |
|-------|--------|---------|
| **Screening (A4)** | ✅ Live | `ScreenApplicationJob` (Gemini per application) |
| **Growth (A1)** | ✅ Live | `RunGrowthAgentJob` · daily · `agents:run-growth` |
| **Support (A3)** | ✅ Live | `RunSupportAgentJob` · hourly · `agents:run-support` |
| **Finance (A5)** | ✅ Live | Stripe webhook + `agents:run-finance` |
| **Onboarding (A2)** | ✅ Live | `RunOnboardingAgentJob` on register · daily batch · `agents:run-onboarding` |
| **Success (A6)** | ✅ Live | On Stripe charge · `success_outreach_drafts` · testimonial request L1 |

| Feature | Location |
|---------|----------|
| AI Operations dashboard | `/ai-operations` |
| Agent registry + daily caps | `AgentRegistry`, `business_agents` table |
| Autonomy L0–L3 on executions | `agent_executions.autonomy_level` |

### Evidence & judges (P0 🏆)

| Feature | Location |
|---------|----------|
| `CompetitionMetrics` service | `app/Services/CompetitionMetrics.php` |
| Public impact page | `/impact` |
| Impact JSON API | `GET /api/v1/impact.json` |
| Snapshot command | `php artisan impact:snapshot` → `docs/evidence/` |
| Demo seeder | `DatabaseSeeder` · demo user, program, sample app, agent history |
| Judge docs | [`commercialization/`](commercialization/) |

### Infrastructure (ready, not deployed)

| Item | Location |
|------|----------|
| Dockerfile | `docker/Dockerfile` |
| Cloud Run deploy (bash) | `scripts/deploy-cloud-run.sh` |
| Cloud Run deploy (PowerShell) | `scripts/deploy-cloud-run.ps1` |
| GCP secrets upload | `scripts/setup-gcp-secrets.ps1` |
| GitHub Actions deploy | `.github/workflows/deploy.yml` |
| Stripe wired into deploy | Secret Manager + env vars for prices |

### Tests

| Suite | Covers |
|-------|--------|
| `ApplicationScreeningFlowTest` | Submit → job → result |
| `StripeCheckoutFlowTest` | Billing, fulfillment, idempotency |
| `RevenueClassifierTest` | BINA slug, Gmail neutral org |
| `FinanceAgentTest`, `OnboardingAgentTest`, `SuccessAgentTest` | Agent + billing hooks |
| `ApplicationDecisionFlowTest` | Decision + founder email send |
| `GeminiClientRetryTest`, `BillingServiceTest`, `CompetitionMetricsTest` | Unit coverage |

---

## Partially done ⚠️

| Item | What exists | What's missing |
|------|-------------|----------------|
| **6 agents story** | All 6 live in production code | Seeded history still mixed with live logs in DB |
| **Decision workflow** | Accept/reject/shortlist/waitlist + impact KPIs | Accept 1 app + snapshot for `jobs_influenced` |
| **Gemini quota** | Retry/backoff in client | Free tier 429s during heavy demo — enable billing/credits |
| **File storage** | GCS driver configured | Production uses `local` disk |
| **Testimonials** | 1 seeded quote on `/impact` | 3+ public verifiable (e.g. Mustafa/BINA LinkedIn) |
| **Evidence pack** | JSON snapshot + markdown docs | Demo video, Stripe PDF, PNG screenshots in repo |
| **Devpost** | Draft + copy prepared | Final submit + gallery complete |

---

## Not implemented ❌ (in spec, not built)

| Feature | Spec phase | Priority |
|---------|------------|----------|
| Decision workflow (accept/reject, score override) | Phase 1 | ✅ Done (override N/A) |
| AI email draft + human-approved send | Phase 1 | ✅ Done |
| Committee report + PDF export | Phase 2 | P2 |
| Programs & rubrics admin CRUD | Phase 0 | P1 |
| Pro tier checkout ($799/mo) | §15 | P2 |
| RBAC (reviewer beyond owner) | Phase 0 | P1 |
| Onboarding Agent (A2) live | Phase 1 | ✅ Done |
| Success Agent (A6) live | Phase 2 | ✅ Done |
| i18n (EN/AR/TR UI) | §19 | Deferred |

---

## Explicitly deferred 🚫

| Item | Reason |
|------|--------|
| **Google Cloud Run production deploy** | 🟡 **Ready** — `setup-gcp-infra.ps1` + `deploy-cloud-run.ps1 deploy` |
| **Cloud SQL + GCS in production** | 🟡 Cloud SQL via deploy script; GCS optional post-gate |
| New agents beyond Screening/Growth/Support/Finance | Not needed for current judge story |
| Live Stripe (non-test) | Test mode sufficient for hackathon evidence |

---

## Submission checklist (Devpost)

| Item | Status |
|------|--------|
| Arms-length Stripe revenue | ✅ $697 (3 customers) |
| Related-party reported separately | ✅ $199 |
| `/impact` + `impact-*.json` | ✅ |
| README Judge Quickstart | ✅ |
| Finance Agent live on charges | ✅ |
| ≥4 agents visible on `/ai-operations` | ✅ **6 live** |
| Decision workflow on application detail | ✅ |
| Founder email draft + send | ✅ |
| Replay screening → activity KPIs > 0 | ✅ `impact-20260611.json` |
| Demo video (< 3 min) | ⬜ Todo — [`DEMO_VIDEO_SCRIPT.md`](commercialization/DEMO_VIDEO_SCRIPT.md) |
| Screenshot set (5 images) | ✅ 4/5 — [`docs/evidence/`](evidence/) (optional: replay-screening PNG) |
| `docs/evidence/revenue-evidence.pdf` | ✅ Generated from `revenue-evidence.html` (or Stripe Dashboard export) |
| Devpost final submit (by Aug 15) | ⬜ Todo |
| Repo public or shared with judges | ⬜ Confirm |
| GCP product in production | 🟡 Run deploy — see [`DEPLOY_CLOUD_RUN.md`](commercialization/DEPLOY_CLOUD_RUN.md) |

---

## Next actions (from ADVANCED_STAGE_GATE — do in order)

**Sprint 1 — Code** ✅ **Done** (Onboarding, Success, decisions, founder email)

**Sprint 2 — KPIs & evidence** (mostly ✅)  
1. ~~Accept one application~~ ✅ App #1 **Sample Startup** accepted  
2. ~~Agents + snapshot~~ ✅ `impact-20260611.json`  
3. ~~**3rd arms-length** checkout~~ ✅ **$697** / 3 customers (`revenue-evidence.json`)  
4. ~~Stripe PDF + screenshots~~ ✅ `revenue-evidence.pdf` + 4 PNGs in `docs/evidence/`  
5. Mustafa **LinkedIn testimonial** URL (public) — still ⬜  

**Sprint 3 — Deploy & submit**  
6. **GCP deploy** — `gcloud auth login` → set `.env` → `.\scripts\deploy-cloud-run.ps1 deploy`  
7. Demo **video** + Devpost **final submit** by Aug 15  

~~Optional after gate green: committee PDF, Pro tier, rubrics CRUD~~

---

## Key commands

```bash
# Local dev
php artisan serve
php artisan queue:work          # or QUEUE_CONNECTION=sync in .env
php artisan schedule:work

# Agents
php artisan agents:run-growth
php artisan agents:run-onboarding
php artisan agents:run-support
php artisan agents:run-finance

# Evidence
php artisan impact:snapshot

# Stripe (local)
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook

# Tests
php artisan test --filter=StripeCheckoutFlowTest
php artisan test --filter=FinanceAgentTest
php artisan test --filter=RevenueClassifierTest
```

---

## Key URLs (local)

| URL | Purpose |
|-----|---------|
| http://127.0.0.1:8000 | App |
| http://127.0.0.1:8000/impact | Judge KPI dashboard |
| http://127.0.0.1:8000/api/v1/impact.json | Machine-readable KPIs |
| http://127.0.0.1:8000/ai-operations | Agent registry + log |
| http://127.0.0.1:8000/billing | Revenue split |

**Demo:** `demo@venturelens.app` / `demo-password-change-me` (related-party for checkout tests)

---

## Documentation index

| Doc | Purpose |
|-----|---------|
| [`ADVANCED_STAGE_GATE.md`](ADVANCED_STAGE_GATE.md) | **Mandatory path for advanced stage** |
| [`VENTURELENS_SYSTEM_REQUIREMENTS.md`](VENTURELENS_SYSTEM_REQUIREMENTS.md) | Full spec + competition strategy |
| [`commercialization/STRIPE_JUDGE_GUIDE.md`](commercialization/STRIPE_JUDGE_GUIDE.md) | Checkout + arms-length testing |
| [`commercialization/JUDGE_EVIDENCE.md`](commercialization/JUDGE_EVIDENCE.md) | Screenshot + API checklist |
| [`commercialization/DEMO_VIDEO_SCRIPT.md`](commercialization/DEMO_VIDEO_SCRIPT.md) | 2:45 video script |
| [`commercialization/DEPLOY_CLOUD_RUN.md`](commercialization/DEPLOY_CLOUD_RUN.md) | GCP deploy when ready |
| [`commercialization/GEMINI_SETUP.md`](commercialization/GEMINI_SETUP.md) | API key + quota |

---

## Changelog

| Date | Change |
|------|--------|
| 2026-06-11 | **GCP deploy scripts** — `setup-gcp-infra`, auto `APP_URL`, seed on boot, deploy docs |
| 2026-06-11 | **Login fix** — `Auth` facade import in `AuthenticatedSessionController` |
| 2026-06-11 | **Sprint 2 KPI boost** — accepted app #1, agents run, `impact-20260611.json` (7 screened, 3 jobs) |
| 2026-06-10 | Sprint 1 verified (3 items) — tests passing; `RegistrationOnboardingTest` added |
| 2026-06-10 | **Sprint 1 shipped** — Onboarding (A2), Success (A6), decision workflow, founder email |
| 2026-06-10 | **`ADVANCED_STAGE_GATE.md`** — mandatory 6-gate plan for advanced stage (A–F) |
| 2026-06-10 | **Milestone update protocol** added — agents must refresh this file each session |
| 2026-06-10 | Arms-length revenue verified (**$498**, 2 customers); related-party **$199** |
| 2026-06-10 | Stripe checkout **Inertia CORS fix** (`Inertia::location` + form POST) |
| 2026-06-10 | Finance Agent live — `stripe_reconcile` on every charge |
| 2026-06-10 | Judge docs: STRIPE_JUDGE_GUIDE, JUDGE_EVIDENCE, DEMO_VIDEO_SCRIPT |
| 2026-06-10 | Stripe Link disabled (`agent_identity_token` fix) |
| 2026-06-10 | Core screening loop + Growth/Support agents + `/impact` shipped |
| 2026-06-10 | Initial repo: Laravel 11, Vue/Inertia, Gemini pipeline, deploy scripts |

---

*Update this file when you ship features, run snapshots, complete Devpost steps, or change deferrals — **before ending the session** if any milestone trigger above applied.*

---

## For Cursor agents

When the user or you complete work on VentureLens:

1. Read `docs/PROJECT_STATUS.md` at session start if planning or reporting status.
2. Update it at session end if anything in **Milestone update protocol** applied — even for doc-only or evidence-only work.
3. Do not ask permission to update status; treat it as part of the deliverable.
4. If you ran `impact:snapshot`, sync **Live KPIs** numbers from the new JSON file.
