# Gemini API — key, quota, and billing setup

VentureLens calls **Google Gemini** for every application screening and for Growth/Support agents. Model default: **`gemini-2.5-flash`**.

## 1. Get an API key

1. Open [Google AI Studio](https://aistudio.google.com/apikey).
2. Create an API key for your Google Cloud / AI Studio project.
3. Add to `.env`:

```env
GEMINI_API_KEY=your_key_here
GEMINI_MODEL_FLASH=gemini-2.5-flash
GEMINI_MODEL_PRO=gemini-2.5-pro
GEMINI_MAX_RETRIES=5
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
| `Model gemini-2.0-flash was shut down` | Update `GEMINI_MODEL_FLASH` in `.env` |
| Screening stuck on "submitted" | Run `php artisan queue:work` (local) or deploy worker to Cloud Run |

## Cost monitoring

- AI Studio → **Usage & Billing** for request counts
- VentureLens dashboard shows **Gemini calls (30d)** per organization
- `/impact` shows platform-wide `gemini_api_calls` from `usage_records`
