# Gemini API — key, quota, and billing setup

VentureLens calls **Google Gemini** for every application screening, **Ask RAG chat**, and Growth/Support agents. Model default: **`gemini-2.5-flash`**.

**Competition:** [Build with Gemini XPRIZE](https://xprize.devpost.com/) · Category: Entrepreneurship & Job Creation

---

## Hackathon credits checklist (Build with Gemini XPRIZE)

Use this before demo video, judge testing, or heavy `/ask` usage. The hackathon does **not** grant unlimited Gemini API calls automatically — you claim **separate perks** and link billing.

### What each perk actually covers

| Perk | Where to claim | Pays for | Fixes `/ask` 429 errors? |
|------|----------------|----------|--------------------------|
| **Devpost registration** | [xprize.devpost.com](https://xprize.devpost.com/) | Official entry, judging, updates | No (required anyway) |
| **$300 Google Cloud credit** | [cloud.google.com/free](https://cloud.google.com/free) or [geminixprize.com](https://www.geminixprize.com/) | Cloud Run, Cloud SQL, Cloud Storage, Vertex AI | **No** — not AI Studio API key calls |
| **AI Studio free tier** | [aistudio.google.com/apikey](https://aistudio.google.com/apikey) | `GEMINI_API_KEY` (what VentureLens uses) | Partial — low RPM, easy to exhaust |
| **AI Studio billing linked to GCP** | AI Studio → Settings → Plan information | Higher quotas on same API key | **Yes — do this** |
| **$100 Google AI Ultra** (orientation perk) | Antigravity app (if offered) | Consumer Ultra product | Not your Laravel API |

> Google documents that the **$300 GCP trial credit cannot pay for Gemini API costs in AI Studio** on the API-key path. Link billing on AI Studio (same GCP billing account) for reliable screening + RAG. Use the $300 credit for **hosting** (`venturelens.app` on Cloud Run + Cloud SQL).

### Checklist (copy and tick off)

- [ ] **Register** on [Devpost](https://xprize.devpost.com/) (same email as GCP is fine).
- [ ] **Claim $300 GCP** on project `venturelens-499513` (or your production project) — card for verification only; trial stops at $300 or 90 days unless you manually upgrade.
- [ ] **Create / confirm AI Studio API key** → paste into `.env` as `GEMINI_API_KEY`.
- [ ] **Link billing in AI Studio** → Settings → Plan information → Set up billing → same GCP billing account as Cloud Run.
- [ ] **Confirm model** in `.env`: `GEMINI_MODEL_FLASH=gemini-2.5-flash` (not deprecated `gemini-2.0-flash`).
- [ ] **Chat tuning** (optional, faster fail on quota):
  ```env
  GEMINI_CHAT_TIMEOUT=30
  GEMINI_CHAT_MAX_RETRIES=2
  ```
- [ ] **Upload secrets + redeploy** (production):
  ```powershell
  .\scripts\setup-gcp-secrets.ps1
  .\scripts\deploy-cloud-run.ps1 deploy
  ```
- [ ] **Smoke test** locally: submit application → Replay screening → `/ask` one question → check logs for `gemini.api_call` (not `rag_chat.failed`).
- [ ] **Share repo** with `testing@devpost.com` and `judging@hacker.fund` (public or private collaborator).
- [ ] **Monitor usage**: AI Studio → Usage & Billing; GCP Console → Billing → Reports (infra only).

### If you still see `Gemini API quota was exceeded`

1. Wait 1–5 minutes (free-tier RPM reset) or confirm billing is linked (not just GCP trial alone).
2. Check AI Studio **Usage** — same project as the API key in Secret Manager.
3. Avoid burst demos: space out Replay screening + multiple `/ask` questions.
4. VentureLens chat already **fails fast** on quota (no 60s retry loops) — errors in &lt;1s mean billing/quota, not vector store.

### Google Antigravity IDE (optional — does not replace production billing)

[Google Antigravity](https://antigravity.google/) is an agent-first IDE in the Gemini hackathon ecosystem. It can speed up **your** development (prototyping, scripts, side experiments) and may offer separate **Ultra / builder perks** — but it is **not** how `venturelens.app` calls Gemini in production.

| | **Antigravity IDE** | **VentureLens production** |
|---|---|---|
| Purpose | Build code with Gemini agents | Run screening, agents, `/ask` for judges |
| Gemini access | IDE / consumer stack | `GEMINI_API_KEY` → Developer API |
| Fixes `/ask` 429 on live site? | **No** | **Yes** — AI Studio billing on `venturelens-499513` |

**Use Antigravity** optionally alongside Cursor for hackathon velocity or to claim builder credits. **Do not** switch the live app to Antigravity as a runtime platform — keep Laravel + Cloud Run + AI Studio API billing for the demo URL judges will test.

### Optional later: burn GCP credits on Gemini API

To charge Gemini calls against **Vertex AI** (and thus the $300 credit), you would migrate from `generativelanguage.googleapis.com` + API key to **Vertex AI** in the same GCP project. VentureLens does not do this today; **linking AI Studio billing** is the fastest path for the hackathon deadline.

**Related:** [`ADVANCED_STAGE_GATE.md`](../ADVANCED_STAGE_GATE.md) (F1, F5) · [`DEPLOY_CLOUD_RUN.md`](DEPLOY_CLOUD_RUN.md) · [`DEVPOST_SUBMISSION.md`](DEVPOST_SUBMISSION.md)

---

## 1. Get an API key

1. Open [Google AI Studio](https://aistudio.google.com/apikey).
2. Create an API key for your Google Cloud / AI Studio project.
3. Add to `.env`:

```env
GEMINI_API_KEY=your_key_here
GEMINI_MODEL_FLASH=gemini-2.5-flash
GEMINI_MODEL_PRO=gemini-2.5-pro
GEMINI_MAX_RETRIES=5
GEMINI_CHAT_TIMEOUT=30
GEMINI_CHAT_MAX_RETRIES=2
```

## 2. Enable billing (required for reliable quota)

New AI Studio accounts often show **`429` with `limit: 0`** until billing is linked — even on the free tier.

1. AI Studio → **Settings** → **Plan information**
2. Click **Set up billing** and link a Google Cloud billing account
3. Free-tier quotas still apply; billing unlocks the quota counters

> **Do not use deprecated models.** `gemini-2.0-flash` was shut down 2026-06-01. Always use `gemini-2.5-flash` or newer.

## 3. Retry behavior (production)

`GeminiClient` automatically retries transient failures:

| Condition | Retries | Backoff |
|-----------|---------|---------|
| HTTP 429 (rate limit) | Up to `GEMINI_MAX_RETRIES` (default 5) | Exponential + jitter; honors `Retry-After` header |
| HTTP 502/503/504 | Same | Exponential + jitter |
| `limit: 0` quota error | **No retry** | Fix model/billing instead |

Retries are logged as `gemini.api_retry` in Laravel logs. Successful recovery logs `gemini.api_call_recovered`.

## 4. Verify screening works

```bash
php artisan serve
php artisan queue:work   # if QUEUE_CONNECTION=database
```

1. Submit an application at `/apply/summer-2026`
2. Or log in → open an application → **Replay screening**
3. Check logs for `gemini.api_call` with token counts

## 5. Production (Cloud Run)

Store the key in **GCP Secret Manager** as `gemini-api-key` and mount via Cloud Run:

```bash
--set-secrets "GEMINI_API_KEY=gemini-api-key:latest"
```

Set env vars on the **worker** service too — screening and agents run on the queue worker.

## Troubleshooting

| Error | Fix |
|-------|-----|
| `GEMINI_API_KEY is not configured` | Set key in `.env` / Secret Manager |
| `limit: 0` on 429 | Switch to `gemini-2.5-flash`; enable billing in AI Studio |
| `exceeded your current quota` on `/ask` | Link AI Studio billing; wait for RPM reset; see **Hackathon credits checklist** above |
| `Model gemini-2.0-flash was shut down` | Update `GEMINI_MODEL_FLASH` in `.env` |
| Screening stuck on "submitted" | Run `php artisan queue:work` (local) or deploy worker to Cloud Run |

## Cost monitoring

- AI Studio → **Usage & Billing** for request counts
- VentureLens dashboard shows **Gemini calls (30d)** per organization
- `/impact` shows platform-wide `gemini_api_calls` from `usage_records`
