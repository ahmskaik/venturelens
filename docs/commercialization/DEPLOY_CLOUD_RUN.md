# Deploy VentureLens to Cloud Run

## Windows (PowerShell)

```powershell
# 1. Set GCP project in .env or environment
$env:GCP_PROJECT_ID = "your-gcp-project-id"
$env:GCP_REGION = "us-central1"

# 2. Ensure .env has Stripe + Gemini + DB_PASSWORD filled in

# 3. Upload secrets + build + deploy
.\scripts\deploy-cloud-run.ps1 deploy
```

## What gets passed to Cloud Run

| Name | Where | From |
|------|--------|------|
| `STRIPE_SECRET` | Secret Manager | `.env` → `stripe-secret` |
| `STRIPE_WEBHOOK_SECRET` | Secret Manager | `.env` or placeholder |
| `STRIPE_KEY` | Secret (if `pk_...`) | `.env` → `stripe-key` |
| `STRIPE_PRICE_COHORT` | Env var | `.env` |
| `STRIPE_PRICE_STARTER` | Env var | `.env` |
| `GEMINI_API_KEY` | Secret Manager | `.env` |
| `APP_KEY` | Secret Manager | `.env` |
| `DB_PASSWORD` | Secret Manager | `.env` |

## GitHub Actions secrets

Add these repository secrets for CI deploy:

- `GCP_PROJECT_ID`, `GCP_REGION`, `GCP_WORKLOAD_IDENTITY_PROVIDER`, `GCP_SERVICE_ACCOUNT`
- `STRIPE_PRICE_COHORT` = `price_1TglZYQmW9Uu8KsuldLYAnPi` (your test price)
- `STRIPE_PRICE_STARTER` = `price_1TglZZQmW9Uu8KsuBaFFbbqb`
- `DEMO_USER_PASSWORD` = `demo-password-change-me`

Upload to GCP Secret Manager (once): `stripe-secret`, `stripe-webhook-secret`, `gemini-api-key`, `venturelens-app-key`, `venturelens-db-password`

## After deploy

1. Get URL: `gcloud run services describe venturelens-web --region us-central1 --format="value(status.url)"`
2. Update service: `gcloud run services update venturelens-web --region us-central1 --set-env-vars APP_URL=https://YOUR-URL`
3. Stripe Dashboard → Webhooks → Add endpoint `https://YOUR-URL/stripe/webhook`
   - Events: `checkout.session.completed`, `invoice.payment_succeeded`, `customer.subscription.updated`
4. Copy signing secret → update `stripe-webhook-secret` in Secret Manager
5. Seed demo data (Cloud Run job or local against Cloud SQL): `php artisan db:seed --force`

## Cohort checkout without Stripe CLI

Hosted Checkout fulfills on `/billing/success` — webhook optional for one-time Cohort payments.
