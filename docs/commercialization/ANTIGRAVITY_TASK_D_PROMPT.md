# Antigravity prompt — TASK D: Evidence Explorer SPA

Copy everything inside the **START PROMPT** block below into a new Antigravity session.

**Context:** Task A (nightly impact archiver) is ✅ deployed. Production is verified at `https://venturelens.app`. Your job is **Task D only**.

---

## START PROMPT

You are building **TASK D — Evidence Explorer** for **VentureLens**, an AI-operated B2B SaaS that screens startup applications for incubators using **Google Gemini** in production.

**Hackathon:** [Build with Gemini XPRIZE](https://xprize.devpost.com) · Entrepreneurship & Job Creation · Deadline Aug 17, 2026  
**Main app (do not modify):** `https://github.com/ahmskaik/venturelens` · **Production:** `https://venturelens.app`  
**GCP project:** `venturelens-499513` · **Region:** `us-central1`

---

### Critical constraints

1. **Do NOT modify the Laravel monorepo.** Deliver a **standalone** Vue 3 + Vite project (folder or separate repo).
2. **Do NOT duplicate** `/impact`, `/widgets/impact/`, or `/ask` — this is a **judge-facing transparency dashboard** focused on live KPIs + agent execution history.
3. **No backend required.** Static SPA only; fetch live data from the public API.
4. **No auth.** Public read-only.
5. **MIT license.** No secrets in code. `.env.example` only if needed (e.g. optional `VITE_API_BASE_URL`).
6. **Gemini not required** for this task — data comes from existing APIs/JSON.

### Why this task matters (judging)

Advances **Category Impact** and **AI-Native Operations**: judges see live production KPIs plus a timeline of agent actions with autonomy levels L0–L3 — proof that “VentureLens is operated by Gemini agents.”

---

### What to build

A **Vue 3 + Vite + Tailwind** single-page app that builds to static `dist/`. Two views (tabs or routes):

#### Page 1 — Live Impact Dashboard

- On load, `fetch('https://venturelens.app/api/v1/impact.json')` (CORS is `*` — no proxy needed).
- Display KPI cards aligned with the existing impact widget and `/impact` page:
  - **Business:** arms-length revenue, paying customers, total revenue
  - **Activity:** applications screened, Gemini API calls, organizations, countries
  - **AI operations:** total agent actions, % decisions by AI, human hours displaced
  - **Impact:** founder hours saved, accepted startups, jobs influenced (modeled)
- Show `generated_at` timestamp and a “Live from production” badge.
- Optional: simple bar chart for `ai_operations.by_agent` (Chart.js or pure CSS bars — keep bundle small).
- Handle loading / error / retry states gracefully.
- Auto-refresh every 60s (configurable).

#### Page 2 — Agent Execution Explorer

- Load a **bundled** `public/sample-agent-executions.json` (ship realistic demo data in repo).
- Also merge/display `recent_agent_executions` from the live `impact.json` response when available (dedupe by `created_at` + `agent_name` + `step`).
- Table or vertical timeline showing:
  - `agent_name` (badge color per agent: growth, onboarding, support, screening, finance, success)
  - `step`, `decision`, `action_taken`
  - **Autonomy badge** L0–L3 with legend:
    - L0 Observe · L1 Recommend · L2 Act with approval · L3 Autonomous
  - `created_at` (relative + absolute)
  - `status` if present
- Filters: by agent name, by autonomy level (multi-select).
- Sort: newest first (default).

#### Global UX

- **Dark/light toggle** (persist in `localStorage`).
- Header: VentureLens logo text + link to `https://venturelens.app/impact`.
- Footer: “Evidence Explorer · Build with Gemini XPRIZE · Data from production API”.
- Mobile-responsive. Professional B2B SaaS tone — no hype.
- **Design tokens** (match main app):
  - Primary: `#4f46e5` (indigo)
  - Text: slate-900 / slate-600
  - Font: Inter, system-ui
- Total built assets **< 2 MB** (gzip).

---

### API contract (live)

**Endpoint:** `GET https://venturelens.app/api/v1/impact.json`

Abbreviated shape (fetch live before hardcoding copy):

```json
{
  "generated_at": "2026-06-11T08:50:16+00:00",
  "business": {
    "arms_length_paying_customers": 3,
    "arms_length_revenue_usd": 697,
    "related_party_revenue_usd": 199,
    "total_revenue_usd": 896
  },
  "activity": {
    "applications_screened": 7,
    "gemini_api_calls": 7,
    "registered_organizations": 7,
    "countries_reached": 4
  },
  "ai_operations": {
    "total_agent_actions": 79,
    "pct_decisions_by_ai": 88.6,
    "by_agent": {
      "finance": 6,
      "growth": 5,
      "onboarding": 5,
      "screening": 57,
      "success": 3,
      "support": 3
    },
    "human_hours_displaced": 12.3,
    "autonomy_distribution": [1, 8, 10, 60]
  },
  "impact": {
    "founder_hours_saved": 5.3,
    "accepted_startups": 1,
    "jobs_influenced_modeled": 3
  },
  "recent_agent_executions": [
    {
      "agent_name": "screening",
      "step": "gemini_screen",
      "decision": "score_78",
      "action_taken": "Screened application with Gemini",
      "autonomy_level": 3,
      "status": "completed",
      "created_at": "2026-06-11T08:50:15+00:00"
    }
  ]
}
```

**Reference files in main repo** (for sample data realism — do not fork the whole app):
- `docs/evidence/impact-20260611.json` — full KPI + `recent_agent_executions`
- `public/widgets/impact/widget.js` — metric labels and formatting patterns
- `database/seeders/DatabaseSeeder.php` → `seedDemoAgentExecutions()` — 15 realistic agent rows

Your `sample-agent-executions.json` should include **≥15 rows** across all 6 agents with mixed L1–L3 autonomy levels.

---

### Deliverable structure

```
evidence-explorer/          # standalone project root
  README.md                 # setup, dev, build, verify
  INTEGRATION.md            # exact merge steps for Laravel team
  LICENSE                   # MIT
  .env.example              # VITE_API_BASE_URL=https://venturelens.app (optional)
  package.json
  vite.config.js            # base: '/evidence-explorer/' for production paths
  index.html
  src/
    App.vue
    main.js
    components/             # KpiCard, AgentTimeline, AutonomyBadge, ThemeToggle, ...
    views/                  # DashboardView, AgentsView
    composables/            # useImpactApi.js, useTheme.js
    assets/
  public/
    sample-agent-executions.json
  dist/                     # after npm run build (gitignore dist; document build step)
```

---

### Acceptance criteria

1. `npm install && npm run dev` — local dev works.
2. `npm run build` — outputs static `dist/` **< 2 MB** total.
3. `npm run preview` (or serve `dist/`) — both pages render with live API data.
4. Agent page shows autonomy badges L0–L3 with filter working.
5. Dark/light toggle persists across reload.
6. `README.md` includes curl/fetch verification steps.
7. `INTEGRATION.md` documents copy target: `venturelens/public/evidence-explorer/` (contents of `dist/`).

---

### INTEGRATION.md must include

1. **What you built** (1 paragraph).
2. **Copy instructions:**
   ```bash
   # From evidence-explorer repo after build:
   cp -r dist/* /path/to/venturelens/public/evidence-explorer/
   ```
   Windows equivalent for PowerShell.
3. **Vite `base` path:** must be `/evidence-explorer/` so assets resolve at `https://venturelens.app/evidence-explorer/`.
4. **No Laravel code changes required** — static files only. Optional note: add link from `/impact` footer later (Cursor team).
5. **Judge verification:**
   - Open `https://venturelens.app/evidence-explorer/`
   - Confirm KPIs match `https://venturelens.app/api/v1/impact.json`
   - Confirm agent timeline shows autonomy badges
6. **Deploy order:** build locally → copy to `public/evidence-explorer/` → redeploy Cloud Run (`deploy-cloud-run.ps1 deploy`) or commit + CI.

---

### Out of scope (do not build)

- Committee PDF export (Task B — deferred)
- OpenAPI docs (Task C)
- Laravel/Inertia changes
- Authentication or write APIs
- New Gemini API calls

---

### Session workflow

1. Confirm scope and file list.
2. Fetch live `https://venturelens.app/api/v1/impact.json` and inspect real field names.
3. Scaffold Vue 3 + Vite + Tailwind with `base: '/evidence-explorer/'`.
4. Build Page 1 (dashboard) then Page 2 (agent explorer).
5. Create `sample-agent-executions.json` from seeder patterns.
6. Write README + INTEGRATION.md + MIT LICENSE.
7. Run build; confirm size < 2 MB; document verification commands.

**Start now.** List the files you will create, then implement.

---

## END PROMPT

---

## After Antigravity finishes (Cursor handoff)

1. Review zip/PR — no API keys committed.
2. `npm run build` in the delivered project.
3. Copy `dist/*` → `public/evidence-explorer/` in main repo.
4. `.\scripts\deploy-cloud-run.ps1 deploy` (or `web` only if image unchanged).
5. Verify `https://venturelens.app/evidence-explorer/`.
6. Update `docs/PROJECT_STATUS.md` changelog.
7. Optional: add footer link on `/impact` → Evidence Explorer.

**Related:** [`ANTIGRAVITY_PROMPT.md`](ANTIGRAVITY_PROMPT.md) (master brief) · [`DEMO_VIDEO_SCRIPT.md`](DEMO_VIDEO_SCRIPT.md)
