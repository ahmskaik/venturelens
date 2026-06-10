# Demo video script & Devpost copy

Target length: **2:45** (under 3 min). Record at 1920×1080, browser zoom 100%, hide bookmarks bar.

**Live numbers** (refresh from `/impact` or `docs/evidence/impact-YYYYMMDD.json` before recording):

| Metric | Current (2026-06-10) |
|--------|----------------------|
| Arms-length revenue | $498 |
| Arms-length customers | 2 |
| Related-party revenue | $199 |
| AI decisions (L2–L3) | 56.3% |
| Agents live | 6 |

---

## Devpost — one-liner & tagline

**One-liner:**  
VentureLens is an AI-operated company that helps every incubator screen startup applications with Gemini — and earns real revenue doing it.

**Tagline:**  
AI-operated screening for incubators. Gemini in production. Real revenue.

**Elevator pitch (30 sec):**  
Incubators receive hundreds of applications and review them by hand for weeks. VentureLens uses Google Gemini to screen every submission against configurable rubrics before humans review. What makes us different: AI doesn't just power the product — six Gemini agents run our sales, support, finance, and operations. We have real Stripe revenue, arms-length customers tracked separately from related-party, and a live `/impact` dashboard judges can verify. We're making fair, fast startup selection accessible to programs worldwide.

---

## Devpost — “What does it do?”

**Problem**  
Program managers at incubators and accelerators drown in applications. Review is slow, inconsistent, and founders wait weeks for feedback.

**Solution**  
VentureLens is a B2B SaaS platform where **every inbound application is screened by Gemini** against program-specific rubrics. Managers get structured scores, risk flags, and committee-ready summaries — with full human override.

**AI-native operations (not just a feature)**  
Six production agents — Growth, Onboarding, Support, Screening, Finance, Success — call Gemini, make decisions, and log every action with an autonomy level (L0–L3). **56% of operational decisions run at L2–L3 without human approval.**

**Business viability**  
Stripe billing (Cohort $199, Starter $299/mo). **$498 arms-length revenue** from 2 paying customers; **$199 related-party** reported separately per competition rules. Live evidence at `/impact` and `GET /api/v1/impact.json`.

**Built with**  
Laravel, Vue, Google Gemini API, Stripe, Google Cloud (target: Cloud Run).

**Try it**  
Demo: `demo@venturelens.app` / `demo-password-change-me` — see [README Judge Quickstart](../../README.md#judge-quickstart-read-this-first).

---

## Video script (~2:45)

| Time | Scene | Voiceover |
|------|-------|-----------|
| 0:00–0:15 | Title card or Welcome page | "Incubators receive hundreds of startup applications every cohort. Manual review takes weeks. Founders wait. Selection is inconsistent. VentureLens fixes that — with Gemini." |
| 0:15–0:45 | Dashboard → Applications → open scored app | "Every application is processed by Gemini before a human sees it. Here's a live score, criterion breakdown, strengths and risks — committee-ready in minutes, not weeks." Click **Replay screening** if Gemini is working; cut to completed result if quota is slow. |
| 0:45–1:15 | `/ai-operations` | "VentureLens isn't a thin AI wrapper. **Six agents run the company** — growth outreach, support tickets, finance reconciliation, screening, onboarding, success. This dashboard shows autonomy levels L0 through L3 and which decisions AI made without a human." Point at **56% L2–L3** and agent bars. |
| 1:15–1:45 | `/impact` (top panel) | "Judges can verify everything live. **498 dollars arms-length revenue**, two paying customers, related-party revenue tracked separately. Stripe charges, agent actions, and KPIs — auto-computed, not hand-made screenshots." Scroll to agent feed; mention Finance classifying revenue. |
| 1:45–2:00 | `/billing` (quick flash) | "Programs upgrade via Stripe — cohort packages or subscriptions. Revenue type is classified at checkout." |
| 2:00–2:15 | Testimonial card on `/impact` | "Program directors report screening a full cohort in a weekend instead of three weeks — founders get feedback the same day." |
| 2:15–2:45 | `/impact` or logo | "VentureLens is an **AI-operated company** expanding fair startup selection to every incubator on earth. Built for the Build with Gemini XPRIZE. Try the demo — link in the README." |

### Optional on-screen overlays

- `Gemini screens 100% of applications`
- `6 AI business agents`
- `$498 arms-length revenue`
- `{APP_URL}/impact`

---

## Recording checklist

1. Run **one Replay screening** so "applications screened" isn't 0 if you show that KPI.
2. Open tabs in order: Applications → AI Operations → Impact → Billing.
3. Refresh evidence: `php artisan impact:snapshot`
4. Demo login: `demo@venturelens.app` / `demo-password-change-me` (use arms-length account only for billing flash; blur email if needed).
5. Upload to YouTube (unlisted OK); paste URL in Devpost.

---

## Devpost field cheat sheet

| Field | Suggested text |
|-------|----------------|
| Project name | VentureLens |
| Tagline | AI-operated startup screening for incubators |
| Category | Entrepreneurship & Job Creation |
| GitHub | *(your repo URL)* |
| Demo URL | Local: `http://127.0.0.1:8000` or production `APP_URL` |
| Video | YouTube link after upload |

---

## Related docs

| Topic | Guide |
|-------|--------|
| Judge screenshots & API | [JUDGE_EVIDENCE.md](JUDGE_EVIDENCE.md) |
| Stripe & revenue split | [STRIPE_JUDGE_GUIDE.md](STRIPE_JUDGE_GUIDE.md) |
| Full spec (Appendix F) | [VENTURELENS_SYSTEM_REQUIREMENTS.md](../VENTURELENS_SYSTEM_REQUIREMENTS.md) |
