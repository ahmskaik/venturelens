# Demo video script & Devpost copy

**Target length:** 2:50 (hard cap **3:00**).  
**Format:** 1920×1080, browser zoom 100%, hide bookmarks bar, cursor highlights only.

**Record on production** (not localhost):

| | |
|---|---|
| **App** | https://venturelens-web-362276424525.us-central1.run.app |
| **Demo login** | `demo@venturelens.app` / `demo-password-change-me` |
| **Impact** | `/impact` |
| **Impact JSON** | `/api/v1/impact.json` |
| **AI Operations** | `/ai-operations` |

---

## Before you record (15 min)

1. **Log in** on production with demo credentials.
2. Open **Applications** → pick a scored app (or app #4 with GCS pitch deck) → click **Replay screening** → wait ~15s until score appears (worker must be healthy).
3. Open **`/impact`** in another tab — **read live numbers from the page** for voiceover (do not use stale figures below).
4. Optional: `php artisan impact:snapshot` against production DB, commit `docs/evidence/impact-YYYYMMDD.json`.
5. Pre-open tabs in order: **Applications** → **AI Operations** → **Impact** → **Billing** → **Apply** (`/apply/summer-2026`).
6. Close unrelated tabs; disable notifications.

### Reference KPIs (refresh from `/impact` before recording)

*Last committed snapshot: 2026-06-11 — verify live on production.*

| Metric | Reference |
|--------|-----------|
| Arms-length revenue | **$697** |
| Arms-length customers | **3** |
| Related-party revenue | **$199** (report separately on Devpost) |
| Applications screened | **7+** |
| Gemini API calls | **7+** |
| Founder hours saved | **5.3+** |
| Accepted startups | **1** |
| Jobs influenced (modeled) | **3** |
| % decisions by AI (L2–L3) | **~89%** |
| Agents live | **6** |

---

## Judging criteria map (must hit all three)

| Criterion | What judges need to see | Where in video |
|-----------|-------------------------|----------------|
| **Business Viability** | Real Stripe revenue, arms-length vs related-party split, SaaS pricing | `/impact` + `/billing` |
| **AI-Native Operations** | Gemini in production; agents run the company; autonomy L0–L3 | Replay screening + `/ai-operations` |
| **Category Impact** | Founder hours saved, programs enabled, jobs influenced | `/impact` narrative |
| **Hard rules** | Gemini API + Google Cloud in production | Replay screening + mention Cloud Run / SQL / GCS |

---

## Devpost — one-liner & tagline

**One-liner:**  
VentureLens is an AI-operated company that helps every incubator screen startup applications with Gemini — and earns real revenue doing it.

**Tagline:**  
AI-operated screening for incubators. Gemini on Google Cloud. Real revenue.

**Elevator pitch (30 sec):**  
Incubators receive hundreds of applications and review them by hand for weeks. VentureLens uses Google Gemini to screen every submission against configurable rubrics — fully automated or human-in-the-loop. What makes us different: AI doesn't just power the product; **six Gemini agents run our sales, support, finance, onboarding, and success operations**. We have real Stripe revenue with arms-length customers tracked separately from related-party, a public `/impact` dashboard judges can verify, and the full stack runs on **Google Cloud Run, Cloud SQL, and Cloud Storage**. We're making fair, fast startup selection accessible to programs worldwide.

---

## Devpost — “What does it do?”

**Problem**  
Program managers at incubators and accelerators drown in applications. Review is slow, inconsistent, and founders wait weeks for feedback.

**Solution**  
VentureLens is a B2B SaaS platform where **every inbound application is screened by Gemini** against program-specific rubrics. Managers get structured scores, risk flags, and committee-ready summaries — with full human override (Accept, Shortlist, Reject, Waitlist).

**AI-native operations (not just a feature)**  
Six production agents — **Screening, Growth, Onboarding, Support, Finance, Success** — call Gemini, make decisions, and log every action with an autonomy level (L0–L3). The `/ai-operations` dashboard shows which decisions ran without human approval.

**Business viability**  
Stripe billing (Cohort **$199**, Starter **$299/mo**). Arms-length revenue from paying customers; related-party revenue reported separately per competition rules. Live evidence at `/impact` and `GET /api/v1/impact.json`.

**Category impact**  
Founder hours saved, accepted startups, and jobs-influenced metrics are computed from production data — not slide-deck estimates.

**Built with**  
Laravel, Vue, Inertia, **Google Gemini API**, Stripe, **Google Cloud Run**, **Cloud SQL (MySQL)**, **Cloud Storage** (pitch decks & logos).

**Try it**  
Production: https://venturelens-web-362276424525.us-central1.run.app  
Demo: `demo@venturelens.app` / `demo-password-change-me`

---

## Video script (~2:50)

Read **live KPIs** from `/impact` where dollar amounts appear.

| Time | Scene | Action | Voiceover |
|------|-------|--------|-----------|
| **0:00–0:12** | Title or Welcome | Show logo + Gemini badge | "VentureLens — AI-operated startup screening for incubators. Built for the Build with Gemini XPRIZE: Entrepreneurship and Job Creation." |
| **0:12–0:22** | Browser URL bar | Show production URL | "This is live on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a prototype." |
| **0:22–0:55** | Dashboard → Applications → open scored app | Show criterion scores, strengths, risks. Click **Replay screening** → cut to completed result if needed | "Every application is processed by Gemini before a human sees it. Structured scores, risk flags, committee-ready summaries — in minutes, not weeks. Managers can run fully automated screening or stay human-in-the-loop with override and committee decisions." |
| **0:55–1:25** | `/ai-operations` | Point at agent registry (6 agents), autonomy chart L0–L3, % decisions by AI, recent execution log | "VentureLens isn't a thin AI wrapper. Six agents run the company — growth outreach, onboarding, support, finance, screening, and success. This dashboard shows autonomy levels and which operational decisions AI made without a human." |
| **1:25–1:55** | `/impact` | Scroll: revenue panel (arms-length vs related-party), applications screened, Gemini calls, founder hours saved, agent feed | "Judges verify everything live. Arms-length revenue and related-party revenue are tracked separately. Applications screened, Gemini API calls, founder hours saved — auto-computed from production data, not hand-made slides." |
| **1:55–2:08** | `/billing` | Show plan, quota, charge history with classification | "Programs upgrade via Stripe — cohort packages or subscriptions. The Finance agent classifies every charge as arms-length or related-party at checkout." |
| **2:08–2:20** | Application detail → decision buttons (optional) | Flash Shortlist / Accept controls or AI-drafted founder email | "Committee decisions stay in human hands — with AI-prepared scores and optional Gemini-drafted founder emails." |
| **2:20–2:32** | `/apply/summer-2026` (optional) | Show form + mention pitch deck upload | "Founders apply through a public intake form. Pitch decks land in Google Cloud Storage and feed multimodal Gemini screening." |
| **2:32–2:50** | `/impact` or Welcome | End card: logo, URL, demo credentials | "VentureLens is an AI-operated company expanding fair startup selection to every incubator. Try the demo — link in the README and Devpost." |

### On-screen overlays (optional lower-thirds)

- `100% applications screened by Gemini`
- `6 AI business agents · L0–L3 autonomy`
- `$697 arms-length · $199 related-party` *(update from live `/impact`)*
- `Google Cloud Run + SQL + Storage`
- `{production URL}/impact`

### If something fails during recording

| Issue | Fallback |
|-------|----------|
| Replay screening slow | Pre-record one successful replay; cut to completed score |
| `/impact` KPI is 0 | Run replay + wait for worker; or use committed `docs/evidence/impact-*.json` and say "snapshot from production" |
| Billing empty | Show `/impact` revenue panel instead |
| Gemini 429 | Use last screened application; mention retry logic in voiceover |

---

## Recording checklist

- [ ] Production health: https://venturelens-web-362276424525.us-central1.run.app/up → 200
- [ ] Worker service healthy (screening completes in ~15s)
- [ ] At least one application with Gemini score visible
- [ ] **Replay screening** run once before filming
- [ ] Tabs pre-opened: Applications → AI Operations → Impact → Billing
- [ ] Live KPI numbers read from `/impact` (not this doc's table)
- [ ] Demo login only — do not expose Stripe secret keys
- [ ] Export 1920×1080, H.264; upload YouTube **unlisted**
- [ ] Paste YouTube URL into Devpost
- [ ] Capture 5 PNG screenshots per [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md)

---

## Devpost field cheat sheet

| Field | Suggested text |
|-------|----------------|
| Project name | VentureLens |
| Tagline | AI-operated startup screening for incubators |
| Category | Entrepreneurship & Job Creation |
| GitHub | *(your repo — public or shared with testing@devpost.com, judging@hacker.fund)* |
| **Demo URL** | https://venturelens-web-362276424525.us-central1.run.app |
| **Video** | YouTube/Vimeo link after upload |
| Login instructions | `demo@venturelens.app` / `demo-password-change-me` |
| Cloud products used | Cloud Run, Cloud SQL, Cloud Storage, Gemini API |
| Arms-length revenue | *(from live `/impact`)* |
| Related-party revenue | *(from live `/impact` — separate field)* |

---

## Related docs

| Topic | Guide |
|-------|--------|
| Judge screenshots & API | [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md) |
| Stripe & revenue split | [STRIPE_JUDGE_GUIDE.md](STRIPE_JUDGE_GUIDE.md) |
| Advanced stage gate | [ADVANCED_STAGE_GATE.md](../ADVANCED_STAGE_GATE.md) |
| Cloud Run deploy | [DEPLOY_CLOUD_RUN.md](DEPLOY_CLOUD_RUN.md) |
| Full spec (Appendix F) | [VENTURELENS_SYSTEM_REQUIREMENTS.md](../VENTURELENS_SYSTEM_REQUIREMENTS.md) |
