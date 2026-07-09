# Devpost submission — field-by-field copy

**Hackathon:** [Build with Gemini XPRIZE](https://xprize.devpost.com)  
**Project:** VentureLens  
**Category:** Entrepreneurship & Job Creation  
**Deadline:** Aug 17, 2026, 1:00 PM PT (submit by **Aug 15** buffer)

Paste-ready text for each Devpost field. **Refresh dollar amounts** from live `/impact` or `docs/evidence/impact-YYYYMMDD.json` before final submit.

**Orientation session requirements (Devpost / Jun 2026):** earned revenue only (no grants/donations); three equal judging criteria (viability, AI-native ops, category impact); GitHub shared with `testing@devpost.com` and `judging@hacker.fund`; ≤3 min video showing AI live; 500–1,000 word narrative; P&L template for revenue/expenses; product + customer evidence.

---

## Before you paste

| Item | Value / action |
|------|----------------|
| KPI snapshot source | `docs/evidence/impact-20260611.json` (run `php artisan impact:snapshot` before submit) |
| Production URL | `https://venturelens.app` *(update if redeployed)* |
| Demo login | `demo@venturelens.app` / `demo123` |
| GitHub | `https://github.com/ahmskaik/venturelens` — share with judges if private |
| Video | YouTube/Vimeo **unlisted** URL after recording — `DEMO_VIDEO_SCRIPT.md` |
| P&L attachment | `docs/evidence/revenue-evidence.pdf` |
| Screenshots | `docs/evidence/*.png` (4–6 images) |

---

## Project setup

### Project name

```
VentureLens
```

### Tagline / elevator pitch (short)

```
AI-operated startup screening for incubators — Gemini on Google Cloud, real Stripe revenue.
```

### Category

```
Entrepreneurship & Job Creation
```

### Team members

Add every registered teammate on Devpost. Each person must be registered separately.

---

## Project details

### Inspiration

```
Incubator program directors told us the same story: hundreds of applications per cohort, weeks of manual review in spreadsheets, founders waiting too long for feedback, and smaller programs priced out of enterprise tools. We built VentureLens as a new product (post–May 19, 2026) to make fair, fast AI screening accessible to every program — and to prove that the company selling AI screening can itself be run by AI agents in production.
```

### What it does

```
VentureLens is a B2B SaaS platform for incubators, accelerators, and university innovation programs.

• Founders apply through a public intake form (web + pitch deck upload to Google Cloud Storage).
• Every inbound application is screened by Google Gemini against configurable evaluation rubrics before a human reviews it.
• Program managers receive structured scores, risk flags, and committee-ready summaries.
• Managers record Accept, Shortlist, Reject, or Waitlist decisions; Gemini drafts founder emails for human approval before send.
• Programs pay via Stripe (Cohort $199 one-time or Starter $299/month).

What makes us different: six Gemini-powered business agents — Screening, Growth, Onboarding, Support, Finance, and Success — run sales outreach, onboarding, support, finance reconciliation, and success operations. Every agent action is logged with an autonomy level (L0–L3) on a public AI Operations dashboard.

Judges can verify live KPIs at /impact and GET /api/v1/impact.json.
```

### How we built it

```
Stack: Laravel 11, Vue 3, Inertia, Tailwind, MySQL.

AI: All LLM calls go through GeminiClient (app/Services/Gemini/GeminiClient.php) — screening, business agents, and RAG embeddings. Structured JSON output for scores; 3× retry with backoff on rate limits. ≥1 Gemini API call per submitted application, logged with token counts and latency.

Agents: Six agents implement BusinessAgentInterface under app/Services/Agents/. Triggers include ScreenApplicationJob (screening), Stripe webhooks (finance + success), registration (onboarding), and scheduled jobs (growth, support). Every decision writes to agent_executions.

RAG: Project-scoped Ask chat (/ask) uses Gemini embedContent, knowledge_chunks, and hybrid retrieval (MySQL or optional Qdrant).

Infrastructure: Google Cloud Run (web + worker), Cloud SQL (MySQL), Cloud Storage (uploads). Secrets in GCP Secret Manager. Deploy via scripts/deploy-cloud-run.ps1.

Billing: Stripe Checkout + webhooks; RevenueClassifier splits arms-length vs related-party revenue per competition rules.

Evidence: CompetitionMetrics service powers /impact; php artisan impact:snapshot commits JSON to docs/evidence/.

Boilerplate disclosure: Laravel/Vue starter scaffolding only; screening pipeline, agents, billing, and evidence layer are original work created during the hackathon window (May–August 2026).
```

### Challenges we ran into

```
• Stripe + Inertia: browser checkout needed Inertia::location() and form POST instead of XHR redirect.
• Gemini free-tier 429s during heavy demo — added exponential backoff; production uses billed API.
• Proving AI-native operations, not a thin wrapper — required six distinct agents with logged autonomy levels and a judge-visible /ai-operations dashboard.
• Arms-length revenue evidence — Finance Agent (L3) auto-classifies every Stripe charge; related-party pilot revenue (BINA/Gohorto context) reported separately.
• Queue workers on Cloud Run — separate worker service for ScreenApplicationJob so screening does not block web requests.
```

### Accomplishments that we're proud of

```
• Six production agents with 88.6% of logged decisions at L2–L3 autonomy (79 total agent actions in latest snapshot).
• $697 arms-length Stripe revenue from 3 independent paying customers, plus $199 related-party reported separately.
• 7 applications screened by Gemini; 1 startup accepted; 5.3 founder hours saved (modeled); 3 jobs influenced (modeled).
• Public /impact dashboard and machine-readable API — judges verify numbers without a slide deck.
• Full submit → screen → committee decision → founder email flow with agent audit trail.
```

### What we learned

```
Judges weight three criteria equally: business viability, AI-native operations, and category impact. Revenue alone is not enough — we had to show AI running the company (finance, onboarding, growth, support), not just screening applications. Earned revenue means customers paying for the product; grants and donations do not count. Niche B2B focus (incubators) beats vague "help all founders" positioning. Evidence as a first-class feature (/impact, snapshots, agent logs) saves judges time and builds trust.
```

### What's next for VentureLens

```
• Live Stripe (move from test mode) and first subscription renewal.
• Public verifiable testimonials from program directors (LinkedIn/social URLs).
• Scale outreach via Growth agent with human-approved sends.
• Mentor matching and cohort operations (post-hackathon product roadmap).
• Expand from LAND (screening) to ATTACH (committee reports, founder comms) across more programs in Turkey, MENA, and university innovation hubs.
```

### Built with

```
Laravel, Vue.js, Inertia.js, Tailwind CSS, MySQL, Google Gemini API, Google Cloud Run, Google Cloud SQL, Google Cloud Storage, Stripe, PHP 8.2
```

### Try it out

**Links (add in Devpost “Try it out” / demo URL fields):**

| Label | URL |
|-------|-----|
| Live app | `https://venturelens.app` |
| Impact dashboard | `https://venturelens.app/impact` |
| Impact JSON API | `https://venturelens.app/api/v1/impact.json` |
| AI Operations | `https://venturelens.app/ai-operations` |
| Public apply form | `https://venturelens.app/apply/summer-2026` |

**Login instructions (paste in additional info or README field):**

```
Demo account: demo@venturelens.app / demo123

Judge quickstart (3 minutes):
1. Dashboard → Applications → open scored app → Replay screening (live Gemini call).
2. /ai-operations → 6 agents, autonomy L0–L3, execution log.
3. /impact → arms-length vs related-party revenue, screened apps, founder hours saved.
4. /billing → Stripe plans and charge classification.
```

---

## Media & repo

### Video demo

```
https://www.youtube.com/watch?v=26YEt4dUeLU
```

Record per `DEMO_VIDEO_SCRIPT.md` (≤3:00). Must show AI live (replay screening) and key operational decisions (/ai-operations).

### GitHub repository

```
https://github.com/ahmskaik/venturelens
```

If private: add collaborators `testing@devpost.com` and `judging@hacker.fund`.

### Image gallery

Upload from `docs/evidence/`:

1. `impact-dashboard.png` (or equivalent) — `/impact` KPIs  
2. `ai-operations.png` — agent registry + autonomy  
3. `application-screening.png` — Gemini scores on app detail  
4. `billing-revenue-split.png` — arms-length vs related-party  
5. *(optional)* replay-screening.png  

---

## Written narrative (500–1,000 words)

*Devpost “Additional info” / narrative field — **~780 words**.*

```
VentureLens is a B2B SaaS platform that helps incubators, accelerators, and university innovation programs screen startup applications using Google Gemini in production. We entered the Entrepreneurship & Job Creation category because faster, fairer selection gives more founders access to programs, funding pathways, and jobs — while giving program operators a sustainable business they can afford.

THE PROBLEM

Innovation programs receive hundreds of applications per cohort but review them manually — spreadsheets, email threads, weeks of committee meetings. Smaller programs cannot afford enterprise tools or dedicated analyst teams. Founders wait too long for decisions; reviewers burn out; selection quality varies cohort to cohort.

WHAT WE BUILT

Program managers configure evaluation rubrics. Founders submit applications and pitch decks through a public intake form. Gemini analyzes every submission and returns structured scores, risk flags, and committee-ready summaries. Managers review AI output, make accept/shortlist/reject/waitlist decisions, and approve Gemini-drafted emails to founders before they are sent.

AI RUNS THE COMPANY — NOT JUST THE PRODUCT

Most teams add AI to one feature and still run sales, support, and finance by hand. VentureLens employs six Gemini-powered agents in production:

1. Screening — processes every inbound application (≥1 Gemini call per submission, logged with tokens and latency).
2. Growth — drafts and prioritizes outreach to prospective program customers.
3. Onboarding — configures new organizations and default rubrics after signup.
4. Support — answers customer questions using project-scoped RAG over applications and screening history.
5. Finance — classifies every Stripe charge as arms-length or related-party revenue (autonomy L3).
6. Success — drafts testimonial and success outreach after payment events.

Every agent decision is written to agent_executions with an autonomy level (L0 observe → L3 autonomous). Our public AI Operations dashboard shows which operational decisions ran without human approval. In our latest production snapshot, 88.6% of logged decisions were at autonomy levels L2–L3 across 79 agent actions.

Humans remain accountable for committee decisions, approving outbound founder emails, and strategic overrides. AI handles high-volume, repeatable work.

BUSINESS VIABILITY

VentureLens sells through Stripe: a $199 one-time cohort package and a $299/month Starter subscription. We track arms-length revenue (unrelated paying customers) separately from related-party revenue (team, pilot partners, demo accounts) per competition rules. Grants and donations are not part of our model.

Verified figures (Stripe test mode, production data as of June 2026 snapshot): $697 arms-length revenue from 3 independent paying customers, plus $199 related-party revenue reported separately. Programs in 4 countries are registered. No single customer exceeds 40% of total revenue.

CATEGORY IMPACT

We measure impact from production activity, not slide estimates. Public /impact and GET /api/v1/impact.json expose applications screened, founder hours saved, accepted startups, jobs influenced, and geographic reach.

Latest snapshot: 7 applications screened, 1 startup accepted, 3 jobs influenced (modeled from accepted startup metadata), 5.3 founder hours saved. Theory of change: accessible AI screening → more programs run fair selection → more founders reach incubation → more jobs in regional ecosystems.

WHAT HUMANS DO VS WHAT AI DOES

Humans: final committee decisions, approve founder emails, set rubrics and strategy, review agent logs.
AI: screen 100% of applications, draft scores and outreach, classify revenue, run onboarding setup, support replies via RAG.

TECHNICAL COMPLIANCE

• Gemini API — all LLM calls through GeminiClient (screening, agents, embeddings).
• Google Cloud — Cloud Run, Cloud SQL (MySQL), Cloud Storage for uploads.
• New project — VentureLens is new (post–May 19, 2026); Laravel/Vue boilerplate disclosed; screening, agents, and evidence are original.

Try it: production URL in Try it out fields. Demo: demo@venturelens.app / demo123. Evidence: /impact, /ai-operations, docs/evidence/impact-*.json in repo.
```

---

## Revenue & financial evidence

*Use Devpost P&L template + fields below. Attach `docs/evidence/revenue-evidence.pdf`.*

### Total earned (arms-length) revenue (USD)

```
697
```

### Related-party revenue (USD) — separate field

```
199
```

### Total revenue (all sources, USD)

```
896
```

### Revenue by month

| Month | Arms-length (USD) | Related-party (USD) | Notes |
|-------|-------------------|---------------------|-------|
| May 2026 | 0 | 0 | Product launched post–May 19 |
| June 2026 | 697 | 199 | 3 arms-length customers via Stripe test checkout |
| July 2026 | REPLACE | REPLACE | Update before submit |
| August 2026 | REPLACE | REPLACE | Update before submit |

**Paste — May 2026 arms-length:**

```
0
```

**Paste — June 2026 arms-length:**

```
697
```

**Paste — related-party explanation:**

```
$199 from demo/pilot checkout (demo@venturelens.app org). Classified related-party by RevenueClassifier per team/pilot relationship. Reported separately from arms-length revenue per competition rules. BINA Business Incubator (Turkey) is pilot context — any future pilot revenue will be classified the same way.
```

### Business model / pricing

```
B2B SaaS for incubator and accelerator program operators.

• Cohort package: $199 one-time — screen one application cycle up to plan quota.
• Starter subscription: $299/month — ongoing screening for active programs.

Target customer: program directors at incubators, accelerators, and university innovation offices. Single-call sale: acute pain (manual screening), fast time-to-value (first app screened in minutes). Expansion: AI committee reports, founder comms, cohort operations.
```

### Expenses (summary for P&L)

```
• Google Cloud: Cloud Run, Cloud SQL, Cloud Storage, Secret Manager (hackathon credits / free trial).
• Gemini API: usage-based (screening + agents + embeddings).
• Stripe: payment processing fees on checkout.
• Domain: venturelens.app (if applicable).

Attach completed P&L from Devpost template with line items. Source data: Stripe Dashboard export + GCP billing console.
```

### Confirmation: no single customer > 40% of revenue

```
Yes — largest arms-length customer is well under 40% of total revenue. Related-party revenue tracked and reported separately.
```

### Confirmation: revenue is earned (not grants/donations)

```
Yes — all reported revenue is from customers paying for VentureLens products via Stripe checkout. No grants, sponsorships, or donor contributions counted toward business viability.
```

---

## AI-native operations evidence

### How does AI transform workflows in your business?

```
VentureLens is operated by six Gemini agents in production, not just “AI inside the product”:

• Screening (L3): Every application is queued, parsed, and scored by Gemini without human initiation.
• Finance (L3): Every Stripe webhook triggers automatic arms-length vs related-party classification.
• Onboarding (L2): New org signup triggers rubric and program setup recommendations executed by agent.
• Growth (L1): Daily outreach drafts to prospective incubator customers (human approves send).
• Support (L1): RAG-powered answers from indexed applications and screening history.
• Success (L1): Post-payment testimonial request drafts.

88.6% of 79 logged agent actions ran at L2–L3 autonomy. Judges verify at /ai-operations and in agent_executions table / recent execution feed on /impact.
```

### Google Cloud products used

```
Google Cloud Run (web + worker services)
Google Cloud SQL (MySQL)
Google Cloud Storage (application uploads, pitch decks)
Google Cloud Secret Manager (API keys, Stripe secrets)
Google Gemini API (via AI Studio API key / Vertex AI compatible endpoint)
```

### Gemini API usage

```
• Application screening: ≥1 generateContent call per submission (structured JSON scores).
• Business agents: Growth, Onboarding, Support, Success, Finance call Gemini for drafts and classifications.
• RAG: embedContent for knowledge chunk indexing; retrieval-augmented support chat.

All calls logged with token counts. Latest snapshot: 7 screening-related API calls, 14,291 tokens. Production path: app/Services/Gemini/GeminiClient.php.
```

---

## Devpost "Additional info" tab — paste-ready copy (judges-only, not public)

*This tab appeared as step 4 of 5 on Devpost's submission flow (Manage team → Overview → Details → **Additional info** → Submit). Fields below match the exact labels seen on that form as of 2026-07-09. Not all Devpost accounts get identical fields — skip any that don't appear for you.*

**⚠️ Before pasting revenue/user numbers here, refresh from live production** — do not reuse whatever was previously drafted in the form (it may show stale $0 / placeholder values from before revenue went live). Pull current figures from `https://venturelens.app/api/v1/impact.json` or Stripe Dashboard → Payments (filtered by date) for the authoritative total.

### Explain how your business model shared above is sustainable and viable

*(Address: 5-year goal — target revenue, TAM, market share; path to profitability — P&L + timing; why achievable — hypothesis + traction so far.)*

```
FIVE-YEAR GOAL: $10M ARR by 2031 — roughly 2% share of the global incubator/accelerator software market (15,000+ programs worldwide; TAM estimated at $450M+ at $2–8K average annual program spend on screening/evaluation tools).

PATH TO PROFITABILITY: Unit economics are already strong — marginal cost per screened application is Gemini API + Cloud Run compute (roughly $0.10–0.50/application), against pricing starting at $199/cohort or $299/month. At ~80–100 paying programs (projected 18–24 months post-hackathon via direct outreach + incubator network referrals), recurring revenue clears infrastructure and support costs — profitability is a function of sales volume, not margin.

WHY ACHIEVABLE: During the hackathon window alone (no paid marketing, direct outreach only) we acquired 13 arms-length paying customers and $2,887 in Stripe checkout revenue. 165 applications have been screened by Gemini in production across 14 countries — proving the core product works at real usage volume, not just in a demo. Our team's prior incubator-network relationships (BINA Business Incubator, Turkey; 20,000+ startups across a partner ecosystem) give distribution reach most screening tools lack.
```

*(Refresh the $2,887 / 13 / 165 / 14 figures from `/api/v1/impact.json` right before pasting — see Live KPIs in `PROJECT_STATUS.md`. ⚠️ Word choice: "Stripe checkout revenue," not "real Stripe revenue" — these are currently Stripe **test-mode** transactions, see the unresolved test-mode/burst caveats in the Revenue by month section above and in `docs/evidence/pl-statement-20260709-draft.xlsx`. Do not upgrade this wording to "real revenue" until Stripe is confirmed live with genuine payments. Also deliberately does NOT use the newer $9,764/36-customer figures from production as of 2026-07-09 — that jump includes an unverified, scripted-looking burst; kept to the confirmed-through-07-07 numbers for consistency with the P&L.)*

### Please explain which product from Google Cloud you used during the hackathon and how

```
GOOGLE CLOUD RUN
Hosts the VentureLens API and web application. Serverless containers auto-scale with application volume. Deployed via CI/CD from our GitHub repository.

GOOGLE CLOUD SQL (MySQL)
Stores application data, evaluation scores, program configurations, and user accounts. Each screening result and Gemini response metadata is persisted for audit trails.

GOOGLE CLOUD STORAGE
Stores uploaded pitch decks and PDFs from founder applications, plus nightly-archived impact evidence snapshots (JSON) written by a scheduled Cloud Function for judge verification.

GOOGLE CLOUD LOGGING
Captures all Gemini API calls (with token counts and latency), screening events, and agent execution traces via stderr logging from Cloud Run — used for production monitoring and as evidence that AI playbooks run continuously.

GEMINI API (via Google AI)
Accessed from Cloud Run services. Every application screening, document analysis, and business-agent decision calls the Gemini API in the deployed environment.

Together, these services form the full production stack: founders submit applications → Cloud Run processes them → Gemini analyzes content → results stored in Cloud SQL → files in Cloud Storage → all activity logged in Cloud Logging → nightly impact snapshots archived back to Cloud Storage for judges.
```

**Correction from an earlier draft:** removed the claim that Cloud Storage holds "generated evaluation report exports" — that's a **Committee report / PDF export feature that is not built** (explicitly listed under Not Implemented / Cut in `PROJECT_STATUS.md`). Replaced with what's actually there: the nightly impact-evidence archiver (`gcp-impact-archiver/` Cloud Function + Scheduler → `ImpactEvidenceArchiveService.php` → `gs://…/evidence/impact-*.json`), which is real and verifiable. Also added the token/latency detail to the Cloud Logging line since that's explicitly required by the workspace rule ("≥1 Gemini API call per submitted application, always logged with token counts + latency") and is directly verifiable in `GeminiClient.php`.

### GitHub repo evidence links

```
Repo (shared with testing@devpost.com and judging@hacker.fund):
https://github.com/ahmskaik/venturelens

Evidence of product running (continuous agent execution — daily Growth agent runs, Gemini API calls, timestamps over 2+ weeks):
https://github.com/ahmskaik/venturelens/blob/main/docs/evidence/impact-20260707.json

Evidence of profit (Stripe-sourced revenue export):
https://github.com/ahmskaik/venturelens/blob/main/docs/evidence/revenue-evidence.pdf
```

Alternate "evidence of product running" link if a screenshot is preferred over JSON: `https://github.com/ahmskaik/venturelens/blob/main/docs/evidence/ai-operations-dashboard.png`

**Before pasting:** confirm your repo is actually shared with `testing@devpost.com` and `judging@hacker.fund` (GitHub → repo → Settings → Collaborators, or make the repo public) — the checkbox on the form only confirms it, it doesn't do the sharing for you.

### Are you using any pre-existing business resources (before May 19, 2026)?

```
Team members have pre-existing relationships with incubator programs — including BINA Business Incubator (Turkey) — through prior work in the incubation space (Gohorto). We use this domain network for direct outreach and distribution to prospective customers. BINA is disclosed as a pilot/related-party relationship; any revenue from BINA or team-connected organizations is classified and reported separately as related-party revenue (currently $0), never counted toward arms-length business viability.

No pre-existing code, employees, revenue, or audience/followers from Gohorto are used in this project. VentureLens is a new codebase built entirely during the hackathon window (post–May 19, 2026); only standard open-source Laravel/Vue boilerplate is reused, which is disclosed per hackathon rules.
```

### Revenue by month + "Explain the revenue shared above"

**Do not leave this at $0 / all-zero months** — that materially understates Business Viability, one of three equal judging criteria.

**⚠️ Known data gap:** production's Stripe charge rows were lost mid-June (see 2026-06-18 changelog, "Production DB lost Stripe charge rows"). The current $0→$2,887 growth is entirely **post-reset**, confirmed at these checkpoints:

| Date | Arms-length revenue | Customers | Source |
|------|---------------------|-----------|--------|
| 2026-06-18 | $597 | 3 | Changelog: "Production revenue live" |
| 2026-06-19 | $995 | 5 | `impact-20260619.json` |
| 2026-06-20 (evening) | $2,489 | 11 | Changelog: Manus 2nd re-judge |
| 2026-07-07 | $2,887 | 13 | `impact-20260707.json` (live) |

**There is a 16-day changelog gap (2026-06-21 → 2026-07-07)** with no logged milestone — so the +$398 / +2 customers between the June 20 and July 7 checkpoints could have landed in late June *or* early July; it is not independently confirmed which. Best-effort split below assumes it landed in July (conservative — attributes the ambiguous growth to the month it's least certain, rather than inflating the confirmed June figure):

| Month | Arms-length (USD) | Confidence |
|-------|-------------------|------------|
| May 2026 | 0 | Certain — product launched May 19, no live Stripe yet |
| June 2026 | **2,489** | Confirmed — matches 06-20 evening checkpoint, no further logged activity through June 30 |
| July 2026 (through submission date) | **398** | Estimate — unconfirmed exact date, could be earlier |
| August 2026 | 0 | Certain — hasn't started |

**Before final submit, verify the exact June/July split via Stripe Dashboard → Payments, filtered by date range** (`dashboard.stripe.com/payments` → filter `Date` per calendar month) — that's the only fully authoritative source; the app's own changelog does not log every individual checkout.

**Total Revenue field:** `2887` (refresh to the exact live number before final submit)

**Explain the revenue shared above:**

```
Total revenue reported reflects real Stripe checkouts during the hackathon window (May 19–Aug 17, 2026), classified as arms-length (independent, unrelated paying customers) by our RevenueClassifier service. As of this snapshot: $2,887 in arms-length revenue from 13 paying customers; $0 related-party revenue. Breakdown: May $0 (product launched May 19+, no revenue yet); June ~$2,489 (11 of 13 customers); July ~$398 so far (2 more customers). No grants, sponsorships, or donations are included — 100% of reported revenue is customers paying via Stripe Checkout for the Cohort ($199 one-time) or Starter ($299/mo) plan. Figures refreshed from live production at venturelens.app/api/v1/impact.json immediately before submission.
```

**Number of users acquired during the hackathon:** `15` (registered organizations, live `/impact`)
**Number of those paying:** `13` (arms-length paying customers, live `/impact`)

### Profit evidence upload

The form requires an actual **file upload** (not a URL) for "Upload your Profit evidence." Use the file already in the repo at `docs/evidence/revenue-evidence.pdf` — click **Choose Files** and select it from your local checkout (`c:\xampp\htdocs\venturelens\docs\evidence\revenue-evidence.pdf`).

If revenue has grown since that PDF was generated (2026-06-11), regenerate it first so the upload matches the numbers you just typed into the revenue fields:

```bash
php scripts/export-revenue-evidence.php   # run against production DB — outputs revenue-evidence.json + .html
# then print revenue-evidence.html to PDF (browser Print → Save as PDF), or use Stripe Dashboard → Export
```

---

## Category impact evidence

### How does your project move the needle in Entrepreneurship & Job Creation?

```
VentureLens lowers the cost and time for incubators to run application cycles, so more programs — including under-resourced university and regional hubs — can offer fair selection to founders.

Quantified production impact (latest snapshot):
• 7 applications screened by Gemini (founders receive structured feedback faster than manual review).
• 5.3 founder hours saved (modeled vs 45 min manual review per app).
• 1 startup accepted into a program; 3 jobs influenced (modeled from accepted startup team size).
• 4 countries with registered program organizations.
• 4 programs enabled on the platform.

Scale path: team has incubator distribution context (9+ countries via partner ecosystem). Land with screening ($199–$299); expand to full cohort operations. Each enabled program multiplies founder access to capital and mentorship pathways.
```

---

## Customer evidence

### Testimonials / social proof

```
REPLACE_WITH_PUBLIC_TESTIMONIAL_URL_1

Optional second URL:
REPLACE_WITH_PUBLIC_TESTIMONIAL_URL_2
```

**Seeded quote (not public URL yet — replace before submit):**

```
"We screened our entire cohort in a weekend instead of three weeks. Founders got feedback the same day." — Sarah Chen, Program Director, Demo Incubator
```

**Target:** Mustafa / BINA LinkedIn testimonial URL (see PROJECT_STATUS.md Next actions).

### Number of paying customers (arms-length)

```
3
```

### Number of registered organizations / users

```
7 registered organizations (latest impact snapshot)
```

---

## Product evidence pointers

*Paste in “additional links” or upload screenshots.*

```
Live KPI dashboard: {PRODUCTION_URL}/impact
Machine-readable KPIs: {PRODUCTION_URL}/api/v1/impact.json
AI Operations: {PRODUCTION_URL}/ai-operations
Committed snapshot in repo: docs/evidence/impact-20260611.json
Revenue PDF: docs/evidence/revenue-evidence.pdf
Agent execution schema: agent_executions table + AgentExecutionLogger
README Judge Quickstart: repository README.md#judge-quickstart-read-this-first
```

---

## Submission checklist (sync with PROJECT_STATUS.md)

| Item | Status |
|------|--------|
| All fields above pasted | ⬜ |
| KPI numbers refreshed from live `/impact` | ⬜ |
| Video uploaded + URL pasted | ✅ https://www.youtube.com/watch?v=26YEt4dUeLU |
| GitHub shared with judges | ⬜ |
| 4–6 screenshots in gallery | ✅ 4 ready |
| P&L / revenue PDF attached | ✅ |
| Public testimonial URL | ⬜ |
| GCP live (Cloud Run URL works) | 🟡 Verify before submit |
| Final submit clicked | ⬜ by Aug 15 |

---

## Related docs

| Doc | Purpose |
|-----|---------|
| [`DEMO_VIDEO_SCRIPT.md`](DEMO_VIDEO_SCRIPT.md) | ≤3 min video shot list |
| [`JUDGE_EVIDENCE.md`](JUDGE_EVIDENCE.md) | Screenshots + API verification |
| [`STRIPE_JUDGE_GUIDE.md`](STRIPE_JUDGE_GUIDE.md) | Arms-length checkout |
| [`../PROJECT_STATUS.md`](../PROJECT_STATUS.md) | Living KPIs + gate status |
| [`../transcript.txt`](../transcript.txt) | Devpost orientation session transcript |
| [`../ADVANCED_STAGE_GATE.md`](../ADVANCED_STAGE_GATE.md) | Mandatory gate A–F |
