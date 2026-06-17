# Judge evidence pack (Devpost)

No new product scope — paths and screenshots judges can verify in ~3 minutes.

## Quick links (local)

| Item | URL |
|------|-----|
| App | http://127.0.0.1:8000 |
| Demo login | `demo@venturelens.app` / `demo-password-change-me` |
| Impact KPIs | http://127.0.0.1:8000/impact |
| Impact JSON | http://127.0.0.1:8000/api/v1/impact.json |
| AI Operations | http://127.0.0.1:8000/ai-operations |
| Billing | http://127.0.0.1:8000/billing |

See [README Judge Quickstart](../../README.md#judge-quickstart-read-this-first) for the 5-click demo script.

## Screenshot checklist

Capture these for Devpost / appendix (1920×1080 or full browser):

1. **Application screening** — Dashboard → Applications → open scored app → criterion breakdown, strengths/risks, agent trace.
2. **Replay screening** — Same app → **Replay screening** → status moves to processing/screened (Gemini call in AI Operations log).
3. **AI Operations** — `/ai-operations` — agent registry, % decisions by AI, autonomy chart, recent executions.
4. **Impact** — `/impact` — arms-length vs related-party revenue, applications screened, founder hours saved, agent feed.
5. **Billing split** — `/billing` — plan, quota, charge history with **arms-length** and **related-party** USD totals.

Optional: Stripe Dashboard (test mode) payment list matching `/billing` charges.

## API verification

```bash
curl -s http://127.0.0.1:8000/api/v1/impact.json | jq .business,.ai_operations
```

Expect `business.arms_length_revenue_usd`, `business.related_party_revenue_usd`, `ai_operations.pct_decisions_by_ai`, and activity counters.

## Committed snapshots

Nightly or post-checkout:

```bash
php artisan impact:snapshot
```

Output: `docs/evidence/impact-YYYYMMDD.json` (commit to repo for judges).

## Demo data & replay

After `php artisan migrate --seed`:

- Demo org `demo-incubator` with sample application and agent execution history.
- **Replay screening** on dashboard re-queues `ScreenApplicationJob` (requires `GEMINI_API_KEY` and queue worker or `QUEUE_CONNECTION=sync`).

## Arms-length vs related-party

Full rules: [`STRIPE_JUDGE_GUIDE.md`](STRIPE_JUDGE_GUIDE.md#arms-length-vs-related-party-checklist).

Use a **new registration** (Gmail + neutral org name) for arms-length Stripe evidence; use **demo login** for related-party split demo.

## Demo video & Devpost copy

| Doc | Purpose |
|-----|---------|
| [`DEMO_VIDEO_SCRIPT.md`](DEMO_VIDEO_SCRIPT.md) | ≤3 min video shot list |
| [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md) | **Paste-ready Devpost fields** (narrative, revenue, AI ops) |
