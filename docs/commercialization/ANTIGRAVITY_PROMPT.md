# Antigravity master prompt — VentureLens

Copy everything inside the **“START PROMPT”** block below into a new Antigravity project. Pick **one task** per session (recommended order at bottom).

**Repo (main app — do not rewrite):** `https://github.com/ahmskaik/venturelens`  
**Production:** `https://venturelens.app`  
**Hackathon:** [Build with Gemini XPRIZE](https://xprize.devpost.com) · Entrepreneurship & Job Creation · Deadline Aug 17, 2026

---

## START PROMPT

You are building **satellite tools** for **VentureLens** — an AI-operated B2B SaaS that screens startup applications for incubators using **Google Gemini** in production.

### Critical constraints

1. **Do NOT rebuild the main application.** The production app is **Laravel 11 + Vue 3 (Inertia) + Cloud Run + Cloud SQL + GCS**. It is already live and qualified for the hackathon.
2. **Do NOT replace** screening, agents, Stripe billing, or `/ask` RAG — those live in the Laravel repo and are maintained in **Cursor**.
3. **Gemini only** for any LLM calls you add (`gemini-2.5-flash` default). Log token usage if applicable.
4. **Deliver standalone repos or folders** with README, `.env.example`, Docker/deploy script, and an **Integration** section explaining how the Laravel team merges or deploys your work.
5. **MIT license.** No secrets in code. Use env vars.
6. Every feature must advance at least one judging criterion:
   - **Business Viability** (real revenue, Stripe, SaaS)
   - **AI-Native Operations** (agents in production, autonomy levels)
   - **Category Impact** (fairer startup selection, measurable KPIs)

### What already exists (do not duplicate)

| Asset | Location / URL | Status |
|-------|----------------|--------|
| Main app | GitHub `venturelens` | Laravel + Vue, 6 agents, Stripe, RAG `/ask` |
| Production URL | `https://venturelens.app` | Cloud Run |
| Public impact page | `/impact` | Live KPIs from DB |
| Public impact API | `GET /api/v1/impact.json` | CORS `*` enabled |
| Judge smoke CLI | `npm run judge:smoke` in main repo | ✅ Done |
| Impact embed widget | `/widgets/impact/` | ✅ Done (deploy to see on production) |
| Demo login | `demo@venturelens.app` / `demo-password-change-me` | Judges |
| Evidence snapshots | `docs/evidence/impact-YYYYMMDD.json` | Committed KPI JSON |

### Public API contract (for your tools)

**Base URL:** `https://venturelens.app`

```
GET  /up                          → health (200 = ok)
GET  /impact                      → HTML impact dashboard
GET  /api/v1/impact.json          → JSON metrics (see schema below)
GET  /api/v1/applications/{id}    → application detail (if auth not required for public id — check before using)
POST /api/v1/programs/{slug}/applications → submit application
```

**`impact.json` shape (abbreviated):**

```json
{
  "generated_at": "ISO8601",
  "business": {
    "arms_length_paying_customers": 3,
    "arms_length_revenue_usd": 697.00,
    "related_party_revenue_usd": 199.00,
    "total_revenue_usd": 896.00
  },
  "activity": {
    "applications_screened": 9,
    "gemini_api_calls": 14,
    "registered_organizations": 7,
    "countries_reached": 4
  },
  "ai_operations": {
    "total_agent_actions": 62,
    "pct_decisions_by_ai": 85.5,
    "by_agent": { "growth": 12, "support": 8, "screening": 20 },
    "human_hours_displaced": 4.2
  },
  "impact": {
    "founder_hours_saved": 6.8,
    "accepted_startups": 1,
    "jobs_influenced_modeled": 3
  },
  "recent_agent_executions": [ { "agent_name", "step", "decision", "autonomy_level", "created_at" } ]
}
```

Refresh numbers from live `https://venturelens.app/api/v1/impact.json` before demo-related copy.

### Known production gaps (main team fixes in Cursor — not your job unless task says so)

- Production `/impact` may show `$0` arms-length revenue until Stripe test checkout is re-run on production DB.
- Gemini API quota: requires **AI Studio billing** linked to GCP project `venturelens-499513` (see `docs/commercialization/GEMINI_SETUP.md` in main repo).

---

## Your mission — pick ONE task below

Build a **complete, deployable deliverable** for the task you choose. Include tests where reasonable.

---

### TASK A — Cloud Function: nightly impact archive → GCS (recommended next)

**Goal:** Extra **Google Cloud** product + automated evidence trail for judges.

**Build:**
- Google Cloud Function (Gen 2, Python 3.11) triggered by **Cloud Scheduler** (daily 02:00 UTC).
- On run: `GET https://venturelens.app/api/v1/impact.json`, validate JSON, write to `gs://venturelens-uploads-venturelens-499513/evidence/impact-YYYYMMDD.json` (or env `GCS_BUCKET`).
- On failure: log to Cloud Logging; optional retry.
- Include: `deploy.sh` or Terraform, README, IAM notes (function needs `storage.objects.create` on bucket).
- **Do not** modify Laravel.

**Acceptance criteria:**
- Manual invoke writes valid JSON to GCS.
- README documents one-command deploy to project `venturelens-499513`, region `us-central1`.

---

### TASK B — Committee screening PDF microservice

**Goal:** Deferred product feature — one-page committee report from application data.

**Build:**
- Stateless **FastAPI** (Python) or **Express** (Node) service.
- `POST /report` body:
  ```json
  {
    "application": {
      "name": "AgriSense",
      "founders": ["..."],
      "narrative": "...",
      "screening_score": 88,
      "risk_flags": ["..."]
    }
  }
  ```
- Call **Gemini** (`gemini-2.5-flash`, JSON mode) for executive summary + recommendation.
- Return **HTML** (print-friendly) + optional **PDF** (weasyprint or puppeteer).
- Env: `GEMINI_API_KEY`. Dockerize. OpenAPI spec included.
- **No database.**

**Acceptance criteria:**
- `curl` example in README produces readable HTML/PDF.
- Integration section: Laravel will add `GET /applications/{id}/export-pdf` proxy later.

---

### TASK C — OpenAPI + Postman collection for public API v1

**Goal:** Platform / integrator story for Business Viability.

**Build:**
- OpenAPI 3.1: `openapi.yaml` for:
  - `GET /api/v1/impact.json`
  - `POST /api/v1/programs/{slug}/applications`
  - `GET /api/v1/applications/{id}`
- Example requests/responses, error schemas.
- `postman_collection.json` + `examples/curl.sh` integration test script.
- Markdown `API.md` with authentication notes (if any endpoints need keys later).

**Acceptance criteria:**
- Postman collection imports and runs against `https://venturelens.app`.
- No changes to Laravel required to **use** the docs (deliver docs-only repo or `docs/api/` folder).

---

### TASK D — Evidence Explorer SPA (static)

**Goal:** Judge-visible transparency for AI operations.

**Build:**
- **Vue 3 + Vite** SPA (no Inertia), build to static `dist/`.
- **Page 1:** Fetch live `/api/v1/impact.json` → KPI dashboard (match widget metrics + charts).
- **Page 2:** Load bundled `sample-agent-executions.json` (provide realistic sample from schema above) → timeline / table of agent actions with autonomy badges L0–L3.
- Tailwind. Dark/light toggle. No auth.
- README: host at `public/evidence-explorer/` in Laravel or GCS static site.

**Acceptance criteria:**
- `npm run build` → static files &lt; 2MB.
- Works when opened from `https://venturelens.app/evidence-explorer/` (CORS already on impact API).

---

### TASK E — Cohort apply-link QR generator

**Goal:** GTM utility for Growth agent story.

**Build:**
- Small static web app or CLI.
- Input: cohort slug (e.g. `summer-2026`).
- Output: full apply URL `https://venturelens.app/apply/{slug}`, QR PNG, printable A4 poster HTML (VentureLens branding, indigo/slate).
- No backend required.

**Acceptance criteria:**
- Works offline after first load (or pure static).
- README with embed instructions for incubator staff.

---

### TASK F — Production status monitor (second Cloud Run service)

**Goal:** Uptime evidence + second deployable on GCP.

**Build:**
- Minimal Node or Go service:
  - `GET /` → status page (green/red)
  - Polls every 60s: `venturelens.app/up`, `/api/v1/impact.json` latency
  - Stores last 24h checks in memory or SQLite
- Dockerfile + Cloud Run deploy script for `venturelens-499513`.
- Public URL documented in README.

**Acceptance criteria:**
- Deploy script works; status page shows last check time and latency ms.

---

## Deliverable format (all tasks)

```
your-repo/
  README.md           # setup, env, deploy, integration with VentureLens
  .env.example
  LICENSE (MIT)
  [source + tests]
  INTEGRATION.md      # exact steps for Laravel team: copy paths, env vars, deploy order
```

**INTEGRATION.md must include:**
1. What you built (1 paragraph)
2. Files to copy into `venturelens` repo (if any) with target paths
3. GCP resources created (names, region)
4. Env vars for Cloud Run / Secret Manager
5. How judges verify it works (URLs, commands)

---

## Design tokens (match VentureLens)

- Primary: `#4f46e5` (indigo brand-600)
- Text: slate-900 / slate-600
- Font: Inter, system-ui
- Tone: professional B2B SaaS, no hype

---

## Recommended order

1. **Task A** — Cloud Function archive (GCP story, evidence automation)
2. **Task B** — PDF microservice (product depth)
3. **Task D** — Evidence Explorer (judge UX)
4. **Task C** — OpenAPI (quick win, docs only)
5. **Task E** or **F** — if time remains

---

## Session kickoff (paste with your chosen task)

```
I am working on VentureLens satellite tooling for the Build with Gemini XPRIZE hackathon.

Read the full brief above. My task for this session: TASK [A|B|C|D|E|F] — [name].

Production base URL: https://venturelens.app
GCP project: venturelens-499513
Region: us-central1

Do not modify the Laravel monorepo. Deliver standalone code with README + INTEGRATION.md + MIT license.
Start by confirming the task scope and listing files you will create, then implement.
```

---

## END PROMPT

---

## After Antigravity finishes

Hand off to **Cursor** on the main `venturelens` repo:

1. Review PR / zip; run security check (no API keys committed).
2. Copy artifacts per `INTEGRATION.md`.
3. Deploy satellite service to GCP if applicable.
4. Run `npm run judge:smoke` on production.
5. Update `docs/PROJECT_STATUS.md` changelog if judge-visible.

**Related docs in main repo:** `GEMINI_SETUP.md` · `DEVPOST_SUBMISSION.md` · `DEMO_VIDEO_SCRIPT.md` · `ADVANCED_STAGE_GATE.md`
