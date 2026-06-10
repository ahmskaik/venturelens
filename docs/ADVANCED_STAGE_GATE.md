# VentureLens — Advanced Stage Gate

**Purpose:** Single source of truth for what is **required** to pass the hackathon, score as **advanced**, and reach **top-5 / prize contention**.  
**Competition:** Build with Gemini XPRIZE · Category: Entrepreneurship & Job Creation  
**Deadline:** Aug 17, 2026, 1:00 PM PT (submit by **Aug 15**)

> If it is not on this list, **do not build it** before the gate is green.

---

## How judges score you (three equal criteria)

| # | Criterion | Advanced means… | VentureLens proof |
|---|-----------|-----------------|-------------------|
| 1 | **Business Viability** | Real arms-length revenue, real users, sustainable SaaS | Stripe + `/billing` + `/impact` |
| 2 | **AI-Native Operations** | AI runs the **company**, not just one feature | 6 agents, `/ai-operations`, logs |
| 3 | **Category Impact** | Measurable entrepreneurship/job creation impact | `/impact` KPIs + narrative + scale path |

**Plus hard rules:** new project (post May 19), **Gemini API in production**, **Google Cloud in production**, code repo for judges.

---

## Gate status (update weekly)

| Gate | Required for | Status |
|------|--------------|--------|
| **A — Compliance** | Acceptance (Stage 1) | 🟡 Partial |
| **B — Viability** | Advanced business score | 🟢 Strong |
| **C — AI-native** | Advanced ops score | 🟡 Good, not advanced |
| **D — Impact** | Advanced category score | 🔴 Weak |
| **E — Evidence** | Finals / top tier | 🟡 Partial |
| **F — Deploy** | Rules + judge access | 🔴 Not done |

**Advanced stage = A + B + C + D + E + F all green.**

---

## A — Compliance (MANDATORY — disqualification if missing)

| # | Requirement | Done? | Action |
|---|-------------|-------|--------|
| A1 | Project created after May 19, 2026 | ✅ | Disclose Gohorto boilerplate in README + Devpost |
| A2 | **≥1 Gemini API call** per application in deployed app | 🟡 Local only | Deploy + replay on production URL |
| A3 | **≥1 Google Cloud product** in production | ❌ | Cloud Run + Cloud SQL (use **$300 hackathon credit**) |
| A4 | Category: Entrepreneurship & Job Creation | ✅ | Devpost |
| A5 | Repo public **or** shared with `testing@devpost.com`, `judging@hacker.fund` | ❌ | GitHub settings |
| A6 | Demo URL + test credentials for judges | 🟡 Local only | README after deploy |
| A7 | Video **< 3 min**, public YouTube/Vimeo | ❌ | Record after A2/A3 green |

**Decision:** GCP deploy is **not optional** for acceptance at advanced level — claim credits, minimal deploy only.

---

## B — Business Viability (MANDATORY for advanced)

| # | Requirement | Floor | Advanced target | Now | Action |
|---|-------------|-------|-----------------|-----|--------|
| B1 | **Arms-length revenue** (USD) | $600 | $1,500+ | $498 | **+1 cohort sale** ($199) via new Gmail org |
| B2 | **Arms-length paying customers** | 3 | 5+ | 2 | Same as B1 |
| B3 | Related-party revenue **reported separately** | Yes | Yes | ✅ $199 | Keep in Devpost separate field |
| B4 | No single customer **> 40%** of revenue | Yes | Yes | ✅ | Stay diversified |
| B5 | **`revenue-evidence.pdf`** in repo | Required | Required | ❌ | Stripe Dashboard → export |
| B6 | Monthly breakdown May–Aug on Devpost | Required | Required | 🟡 | From Stripe dates |
| B7 | Sustainable model visible | Subscription + cohort | ✅ Stripe tiers | ✅ | Show in video |

**Cut:** Pro tier ($799), live Stripe (non-test) — not required.

---

## C — AI-Native Operations (MANDATORY for advanced)

| # | Requirement | Minimum | Advanced | Now | Action |
|---|-------------|---------|----------|-----|--------|
| C1 | **Live agents in production** | 4 | **6** | **6 live** | ✅ Sprint 1 |
| C2 | Screening = 100% inbound via Gemini | Yes | Yes | ✅ | Show in video |
| C3 | **`/ai-operations`** dashboard | Yes | Yes | ✅ | Screenshot |
| C4 | **% decisions by AI** (L2–L3) | 50% | **75%+** | 56.3% | Run all agents before snapshot |
| C5 | Agent execution logs in repo evidence | Yes | Yes | 🟡 | `impact-*.json` + PNG |
| C6 | AI beyond screening (sales/support/finance) | Yes | Yes | ✅ Growth, Support, Finance | Demo in video |

### C1 detail — six agents (non-negotiable for “advanced”)

| Agent | Must be live? | Minimum viable implementation |
|-------|---------------|------------------------------|
| Screening | ✅ Done | Already live |
| Growth | ✅ Done | Already live |
| Support | ✅ Done | Already live |
| Finance | ✅ Done | Already live |
| **Onboarding** | **YES** | On org signup: Gemini proposes rubric + program config → log L2 |
| **Success** | **YES** | After cohort payment: Gemini drafts testimonial request → log L1 |

**Cut:** Mentor matching, new agent types, Pro automation.

---

## D — Category Impact (MANDATORY for advanced)

| # | Requirement | Now | Action |
|---|-------------|-----|--------|
| D1 | **`applications_screened` > 0** on `/impact` | 0 ❌ | Replay screening + `impact:snapshot` |
| D2 | **`gemini_api_calls` > 0** on `/impact` | 0 ❌ | Same (UsageTracker on successful screen) |
| D3 | **`founder_hours_saved` > 0** | 0 ❌ | Auto from D1 |
| D4 | **≥1 accepted startup** (demo OK) | 0 ❌ | **Decision workflow** — accept 1 app in admin ✅ shipped |
| D5 | **`jobs_influenced_modeled` > 0** | 0 ❌ | Follows from D4 |
| D6 | **Verifiable testimonial** (public URL) | null ❌ | Mustafa/BINA LinkedIn post |
| D7 | Scale narrative (9 countries, 20k startups) | 🟡 | Devpost + `/impact` copy |

### D4 detail — decision workflow (minimum)

Required for advanced **product** + **impact** (small scope):

- Admin: **Accept / Reject / Shortlist** on application detail
- Log decision to `agent_executions` (human L2, AI prepared score)
- Optional but high value: **Gemini-drafted founder email** (manager approves → send)

**Cut:** Committee PDF export, side-by-side compare (P1 after gate green).

---

## E — Evidence pack (MANDATORY for top tier)

| # | Asset | Path / location | Done? |
|---|-------|-----------------|-------|
| E1 | Impact JSON snapshot | `docs/evidence/impact-YYYYMMDD.json` | 🟡 Stale KPIs |
| E2 | Stripe revenue PDF | `docs/evidence/revenue-evidence.pdf` | ❌ |
| E3 | Screenshot: screening + agent trace | `docs/evidence/application-screening.png` | 🟡 |
| E4 | Screenshot: `/ai-operations` | `docs/evidence/ai-operations-dashboard.png` | ❌ |
| E5 | Screenshot: `/impact` | `docs/evidence/impact-page.png` | ❌ |
| E6 | Screenshot: `/billing` revenue split | `docs/evidence/billing-split.png` | ❌ |
| E7 | Demo video < 3 min | YouTube/Vimeo URL on Devpost | ❌ |
| E8 | Devpost gallery (4–6 images, 3:2, <5MB) | Devpost | 🟡 |
| E9 | Devpost **final submit** | Devpost | ❌ |
| E10 | README Judge Quickstart (production URL) | `README.md` | 🟡 Local only |

Script: [`commercialization/DEMO_VIDEO_SCRIPT.md`](commercialization/DEMO_VIDEO_SCRIPT.md)  
Checklist: [`commercialization/JUDGE_EVIDENCE.md`](commercialization/JUDGE_EVIDENCE.md)

---

## F — Production deploy (MANDATORY for rules + finals)

| # | Step | Notes |
|---|------|-------|
| F1 | Claim **$300 GCP credit** from [geminixprize.com](https://www.geminixprize.com/) | $0 out of pocket |
| F2 | `setup-gcp-secrets.ps1` + `deploy-cloud-run.ps1 deploy` | Minimal: web + worker |
| F3 | Cloud SQL (smallest tier) + Cloud Run scale-to-zero | Stay in credits |
| F4 | Set `APP_URL`, production Stripe webhook | Test mode OK |
| F5 | Enable **Gemini API billing** (fix 429) | Required for live demo |
| F6 | Smoke test: login → replay → ai-ops → impact → billing | Before video |
| F7 | Update Devpost “Try it out” + README URLs | Production URL |

**Cut:** Custom domain, Pro infra, GCS until post-gate.

---

## Explicitly NOT required (do not build before gate green)

- Pro tier ($799/mo)
- Programs/rubrics admin CRUD
- RBAC reviewers
- Committee PDF / side-by-side export
- i18n UI (EN/AR/TR)
- Mentor matching
- White-label / multi-tenant subdomains
- 8+ customers / $4k revenue (stretch only)

---

## Execution plan (ordered — do not reorder)

### Sprint 1 — Product gaps for advanced (3–5 days code) ✅ **Shipped 2026-06-10**

1. ✅ **Onboarding Agent (A2)** — live on registration  
2. ✅ **Success Agent (A6)** — live after payment  
3. ✅ **Decision workflow** — accept/reject/shortlist/waitlist + impact KPIs  
4. ✅ **Gemini email draft** (approve → send)  

### Sprint 2 — KPIs & evidence (1–2 days)

5. Replay screening on demo app  
6. Run all agents: `agents:run-growth`, `agents:run-support`, `agents:run-finance`  
7. `php artisan impact:snapshot` → commit JSON with activity > 0  
8. +1 arms-length checkout ($199) → **$697 arms-length** total  
9. Stripe PDF + 5 PNG screenshots → `docs/evidence/`  
10. Mustafa testimonial LinkedIn URL → config + Devpost  

### Sprint 3 — Deploy & submit (2–3 days)

11. GCP deploy on credits  
12. Production smoke test  
13. Record demo video (Appendix F script)  
14. Complete Devpost all fields + gallery  
15. **Final submit by Aug 15**  

---

## Advanced stage definition (when you can stop building)

You are **advanced stage ready** when ALL are true:

```
✅ A: Production URL on Google Cloud Run, Gemini live on that URL
✅ B: Arms-length revenue ≥ $600, 3+ customers, revenue-evidence.pdf
✅ C: 6 agents with live agent_executions in last 7 days, pct_decisions_by_ai ≥ 60%
✅ D: applications_screened ≥ 10, founder_hours_saved > 0, jobs_influenced ≥ 3, 1 real testimonial URL
✅ E: Video live, 5 screenshots committed, Devpost submitted
✅ F: Judges can log in and complete 5-click demo on production
```

---

## Master Cursor prompt (Sprint 1 only)

Paste into VentureLens repo:

```
Implement ONLY docs/ADVANCED_STAGE_GATE.md Sprint 1 items — advanced stage positioning:

1. OnboardingAgent (A2): on Organization created, Gemini proposes default rubric + program settings; log to agent_executions (L2); schedule optional daily pass for orgs without programs.
2. SuccessAgent (A6): after RevenueCharge created, Gemini drafts testimonial request email stored for owner review; log L1.
3. Decision workflow: POST /applications/{id}/decision (accept|reject|shortlist|waitlist); audit trail; update impact KPIs (accepted_startups).
4. Founder email draft: on decision, Gemini drafts email; admin approves and sends (log sent).

Do NOT: GCP deploy, Pro tier, PDF export, i18n, new pages beyond minimal UI on existing application detail.

After each item: run tests, update docs/PROJECT_STATUS.md per milestone protocol, summarize judge-visible proof.
```

---

## Tie-break reminder

If tied, judges use: **Business Viability → AI-Native Operations → Category Impact**.  
Prioritize **B1 revenue** and **C1 six agents** before nice-to-have product depth.

---

*Last updated: 2026-06-10 · Sync with [`PROJECT_STATUS.md`](PROJECT_STATUS.md)*
