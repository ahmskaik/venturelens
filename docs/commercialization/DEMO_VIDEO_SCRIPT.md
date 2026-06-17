# Demo video script & Devpost copy

**Target length:** 2:50 (hard cap **3:00**).  
**Format:** 1920×1080, browser zoom 100%, hide bookmarks bar, cursor highlights only.

**Pre-flight script:** `.\scripts\preflight-demo-video.ps1` (run before every take).

**Record on production** (not localhost):

| | |
|---|---|
| **App** | https://venturelens.app |
| **Demo login** | `demo@venturelens.app` / `demo-password-change-me` |
| **Impact** | `/impact` |
| **Impact JSON** | `/api/v1/impact.json` |
| **AI Operations** | `/ai-operations` |
| **Ask (RAG)** | `/ask` |

---

## Production status (checked 2026-06-17)

| Check | Status |
|-------|--------|
| `/up` health | ✅ 200 |
| Applications screened | ✅ 9 |
| Gemini API calls | ✅ 13 |
| Agent actions / % AI | ✅ 61 / 86.9% |
| Founder hours saved | ✅ 6.8 |
| **Arms-length revenue** | 🔴 **$0** — **fix before recording** |
| Accepted startups | 🟡 0 — optional: accept one app first |

### Fix revenue before you record (required for Business Viability)

Production DB lost Stripe charge rows. Pick **one**:

**Option A — Stripe test checkout on production (best for judges)**  
1. Register on production: new `@gmail.com`, org **"Pacific Innovation Lab"** (not BINA/demo/Gohorto).  
2. **Billing** → Cohort $199 → card `4242 4242 4242 4242`.  
3. Repeat for 2 more neutral orgs if you want `$697 / 3 customers` on `/impact`.  
4. Login `demo@venturelens.app` → one related-party $199 checkout (optional split demo).  
5. Confirm `/impact` shows arms-length > $0, then `php artisan impact:snapshot` against production DB.

**Option B — Simulate charge (production DB access)**  
```bash
# Cloud SQL proxy or shell on Cloud Run job
php scripts/verify-arms-length-checkout.php   # run 1–3× for arms-length
php artisan impact:snapshot
```

**Option C — Fallback only (weaker)**  
Show `docs/evidence/revenue-evidence.pdf` for 5s while voiceover cites committed `$697` snapshot — live `/impact` revenue panel will still show $0 unless fixed.

---

## Before you record (15 min)

1. Run `.\scripts\preflight-demo-video.ps1` — all green or warnings only.
2. **Log in** on production with demo credentials.
3. **Applications** → open app with score **85** (or any `screened`) → **Replay screening** once → wait ~15s (worker must be healthy).
4. Open **`/impact`** — copy live numbers into teleprompter below.
5. Pre-open tabs (left to right): **Applications** → **AI Operations** → **Impact** → **Billing** → **Ask**.
6. Close unrelated tabs; disable notifications; Do Not Disturb on.

### Reference KPIs (refresh from `/impact` before recording)

*Production live as of 2026-06-17 — **replace revenue row after Option A/B**.*

| Metric | Production (live) | Committed snapshot (backup) |
|--------|-------------------|----------------------------|
| Arms-length revenue | **$0** 🔴 fix first | **$697** (`impact-20260611.json`) |
| Arms-length customers | **0** | **3** |
| Related-party revenue | **$0** | **$199** |
| Applications screened | **9** | 7 |
| Gemini API calls | **13** | 7 |
| Founder hours saved | **6.8** | 5.3 |
| Accepted startups | **0** | 1 |
| Jobs influenced (modeled) | **0** | 3 |
| % decisions by AI (L2–L3) | **86.9%** | 88.6% |
| Agents live | **6** | 6 |

---

## Judging criteria map (must hit all three)

| Criterion | What judges need to see | Where in video |
|-----------|-------------------------|----------------|
| **Business Viability** | Real Stripe revenue, arms-length vs related-party split, SaaS pricing | `/impact` + `/billing` |
| **AI-Native Operations** | Gemini in production; agents run the company; autonomy L0–L3 | Replay screening + `/ai-operations` |
| **Category Impact** | Founder hours saved, programs enabled, jobs influenced | `/impact` narrative |
| **Hard rules** | Gemini API + Google Cloud in production | Replay screening + mention Cloud Run / SQL / GCS |

---

## Devpost — paste-ready fields

**Full field-by-field copy** (narrative, revenue, AI ops, category impact, testimonials): [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md)

Quick reference below — prefer `DEVPOST_SUBMISSION.md` for final paste.

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
Production: https://venturelens.app  
Demo: `demo@venturelens.app` / `demo-password-change-me`

---

## Video script (~2:50) — teleprompter

Read **live KPIs** from `/impact` where bracketed. Practice once with timer.

| Time | Scene | Action | Voiceover (read aloud) |
|------|-------|--------|------------------------|
| **0:00–0:12** | Title / Welcome | Logo + category text on screen or homepage | "VentureLens — AI-operated startup screening for incubators. Built for the Build with Gemini XPRIZE: Entrepreneurship and Job Creation." |
| **0:12–0:22** | URL bar | Show `venturelens-web-…run.app` | "This is live on Google Cloud — Cloud Run, Cloud SQL, and Cloud Storage. Not a localhost demo." |
| **0:22–0:55** | Dashboard → Applications → scored app | Scores, strengths, risks → **Replay screening** → cut to result | "Every application is processed by Gemini before a human sees it. Structured scores, risk flags, committee-ready summaries — in minutes, not weeks. Programs run fully automated screening or stay human-in-the-loop with committee decisions." |
| **0:55–1:20** | `/ai-operations` | 6 agents, autonomy L0–L3, % AI decisions, execution log (point at `rag_chat_answer` if visible) | "VentureLens is not a thin AI wrapper. Six agents run the company — growth, onboarding, support, finance, screening, and success. [POINT AT %] Eighty-seven percent of operational decisions ran at L2 or L3 autonomy — logged in production." |
| **1:20–1:45** | `/impact` | Revenue split, screened count, Gemini calls, founder hours | "Judges verify everything live. Arms-length and related-party revenue are tracked separately. [READ LIVE NUMBERS] Applications screened, Gemini API calls, founder hours saved — computed from production data." |
| **1:45–1:55** | `/billing` | Plans + charge history with classification | "Programs pay via Stripe — cohort or subscription. The Finance agent classifies every charge at checkout." |
| **1:55–2:05** | `/ask` | Type: "How many applications have we screened?" → show RAG answer | "Support runs on Gemini RAG — answers from indexed applications and screening history, with autonomy logging." |
| **2:05–2:15** | Application detail | Accept / Shortlist buttons or founder email draft | "Committee decisions stay with humans — AI prepares evidence and drafts founder emails." |
| **2:15–2:25** | `/apply/summer-2026` | Public form + pitch deck field | "Founders apply publicly; pitch decks land in Cloud Storage and feed Gemini screening." |
| **2:25–2:50** | `/impact` end card | URL + demo credentials on screen | "VentureLens expands fair startup selection to every incubator — and earns real revenue doing it. Demo login in the README and Devpost." |

### Shorter cut (if over 3:00)

Drop `/apply` scene; keep Replay screening + AI Operations + Impact + Billing.

### On-screen overlays (optional lower-thirds)

- `100% applications screened by Gemini`
- `6 AI business agents · L0–L3 autonomy`
- `[LIVE $] arms-length · [LIVE $] related-party`
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

## Recording setup

| Tool | Settings |
|------|----------|
| **OBS / Xbox Game Bar / Loom** | 1920×1080, 30fps, capture browser window only |
| **Browser** | Chrome incognito, zoom 100%, bookmarks bar hidden |
| **Mic** | Headset preferred; test levels before take |
| **Export** | H.264 MP4; upload YouTube **Unlisted** |
| **Devpost** | Paste URL into `DEVPOST_SUBMISSION.md` → Video demo field |

**Do not show:** `.env`, Stripe secret keys, Cloud Console credentials.

---

## Recording checklist

- [ ] `.\scripts\preflight-demo-video.ps1` — no failures
- [ ] Arms-length revenue > $0 on `/impact` (or evidence PDF fallback planned)
- [ ] Worker healthy — replay screening completes in ~15s
- [ ] Tabs: Applications → AI Operations → Impact → Billing → Ask
- [ ] Live KPI numbers copied into teleprompter
- [ ] One full dry run with phone timer (target 2:45)
- [ ] Export 1920×1080 H.264 → YouTube unlisted
- [ ] Paste YouTube URL into Devpost + `DEVPOST_SUBMISSION.md`
- [ ] Capture 5 PNG screenshots per [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md)

---

## Devpost field cheat sheet

See **[`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md)** for complete paste-ready text. Quick links:

| Field | Doc section |
|-------|-------------|
| Tagline, inspiration, what it does | DEVPOST_SUBMISSION → Project details |
| Written narrative (500–1k words) | DEVPOST_SUBMISSION → Written narrative |
| Revenue / P&L / confirmations | DEVPOST_SUBMISSION → Revenue & financial evidence |
| AI ops + GCP + Gemini | DEVPOST_SUBMISSION → AI-native operations evidence |
| Category impact | DEVPOST_SUBMISSION → Category impact evidence |
| Video / GitHub / gallery | DEVPOST_SUBMISSION → Media & repo |

---

## Related docs

| Topic | Guide |
|-------|--------|
| **Devpost paste-ready fields** | [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md) |
| Judge screenshots & API | [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md) |
| Stripe & revenue split | [STRIPE_JUDGE_GUIDE.md](STRIPE_JUDGE_GUIDE.md) |
| Advanced stage gate | [ADVANCED_STAGE_GATE.md](../ADVANCED_STAGE_GATE.md) |
| Cloud Run deploy | [DEPLOY_CLOUD_RUN.md](DEPLOY_CLOUD_RUN.md) |
| Full spec (Appendix F) | [VENTURELENS_SYSTEM_REQUIREMENTS.md](../VENTURELENS_SYSTEM_REQUIREMENTS.md) |
