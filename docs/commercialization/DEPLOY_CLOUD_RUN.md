# Deploy VentureLens to Cloud Run

Minimal production stack: **Cloud Run** (web + queue worker) + **Cloud SQL** (MySQL 8) + **Secret Manager**. Meets the Gemini XPRIZE rule: ≥1 Google Cloud product in production.

**Estimated cost:** db-f1-micro + scale-to-zero Cloud Run fits in the **$300 hackathon credit** if you tear down after judging.

---

## Prerequisites

| Requirement | Notes |
|-------------|--------|
| GCP project | [Google Cloud Console](https://console.cloud.google.com) — link billing / claim XPRIZE $300 credit |
| gcloud CLI | [Install Cloud SDK](https://cloud.google.com/sdk/docs/install) (Windows: run installer, restart terminal) |
| Docker Desktop | Running (builds image locally before push) |
| `.env` filled | See checklist below |

---

## One-time `.env` checklist

Add to your `.env` (copy from `.env.example` if needed):

```env
GCP_PROJECT_ID=your-gcp-project-id
GCP_REGION=us-central1
DB_PASSWORD=choose-a-strong-password-min-12-chars

APP_KEY=base64:...          # php artisan key:generate
GEMINI_API_KEY=...
STRIPE_SECRET=sk_test_...
STRIPE_KEY=pk_test_...
STRIPE_PRICE_COHORT=price_...
STRIPE_PRICE_STARTER=price_...
STRIPE_WEBHOOK_SECRET=      # optional until webhook registered
DEMO_USER_PASSWORD=demo-password-change-me
```

`DB_PASSWORD` is used for Cloud SQL user `venturelens` and must match what Secret Manager stores.

---

## Deploy (Windows PowerShell)

```powershell
cd c:\xampp\htdocs\venturelens

# 1. Authenticate (once)
gcloud auth login
gcloud config set project YOUR_PROJECT_ID

# 2. Full deploy: infra + secrets + build + Cloud Run
.\scripts\deploy-cloud-run.ps1 deploy
```

**First deploy takes ~15–20 min** (Cloud SQL create ~10 min + Docker build ~5 min).

### Sub-commands

| Command | Purpose |
|---------|---------|
| `.\scripts\deploy-cloud-run.ps1 infra` | Cloud SQL + Artifact Registry + IAM only |
| `.\scripts\deploy-cloud-run.ps1 secrets` | Upload `.env` secrets to Secret Manager |
| `.\scripts\deploy-cloud-run.ps1 build` | Docker build + push |
| `.\scripts\deploy-cloud-run.ps1 web` | Redeploy web service only |
| `.\scripts\deploy-cloud-run.ps1 worker` | Redeploy queue worker only |

## Deploy (Linux / macOS / Cloud Shell)

```bash
export GCP_PROJECT_ID=your-gcp-project-id
export GCP_REGION=us-central1
./scripts/deploy-cloud-run.sh deploy
```

---

## What gets created

| Resource | Name | Purpose |
|----------|------|---------|
| Cloud SQL | `venturelens` (MySQL 8, db-f1-micro) | Sessions, queue, app data |
| Artifact Registry | `venturelens` repo | Docker images |
| Cloud Run | `venturelens-web` | HTTP on port 8080, public |
| Cloud Run | `venturelens-worker` | `queue:work` + `schedule:work` |
| Secret Manager | `venturelens-app-key`, `gemini-api-key`, `stripe-*`, `venturelens-db-password` | Runtime secrets |

On first boot the web container runs `migrate --force` and `db:seed --force` (idempotent demo data).

---

## After deploy

The script sets `APP_URL` automatically and prints:

```
Deployed. Web URL: https://venturelens-web-xxxxx-uc.a.run.app
  Impact:   .../impact
  Health:   .../up
```

### Verify

1. Open `{URL}/up` → `{"status":"ok"}`
2. Open `{URL}/impact` → KPIs (empty revenue until you run test checkouts on production URL)
3. Login `demo@venturelens.app` / `demo-password-change-me`

### Stripe webhook (optional for Cohort one-time)

1. Stripe Dashboard → Webhooks → Add endpoint `{URL}/stripe/webhook`
2. Events: `checkout.session.completed`, `invoice.payment_succeeded`, `customer.subscription.updated`
3. Copy signing secret → update Secret Manager:
   ```powershell
   # Update .env STRIPE_WEBHOOK_SECRET, then:
   .\scripts\deploy-cloud-run.ps1 secrets
   .\scripts\deploy-cloud-run.ps1 web
   ```

Cohort checkout also fulfills on `/billing/success` without webhook.

### Custom domain (`venturelens.app`)

Cloud Run URL works for the hackathon. Map a custom domain when you want a shorter judge/demo link.

**Prerequisites:** Domain registered (e.g. Connaxis). Remove parking-page DNS that points elsewhere.

1. **Verify domain in Google** (one-time):

   ```powershell
   .\scripts\map-custom-domain.ps1 verify
   ```

   Search Console opens → choose **DNS TXT** → add the TXT record at your registrar on `@` → click **Verify**.

2. **Create mapping + show DNS + set `APP_URL`:**

   ```powershell
   .\scripts\map-custom-domain.ps1 apply
   # Optional www: .\scripts\map-custom-domain.ps1 apply -IncludeWww
   ```

3. At Connaxis (or your DNS host), add the **A / CNAME records** printed by the script. Delete conflicting parking records.

4. Wait 15–60 min for DNS; SSL cert may take up to 24h. Check:

   ```powershell
   .\scripts\map-custom-domain.ps1 status
   ```

5. Update **Stripe webhook** to `https://venturelens.app/stripe/webhook` and refresh secrets if needed.

Use **https://** only (`.app` enforces HTTPS).

### Re-seed / refresh KPIs on production

```bash
# Cloud Shell with Cloud SQL proxy, or temporarily set RUN_SEED=true and redeploy web
gcloud run services update venturelens-web --region us-central1 --update-env-vars RUN_SEED=true
# After one request/revision, remove RUN_SEED to avoid re-running seed each cold start
```

For judge evidence on production, run checkouts against the live URL and `php artisan impact:snapshot` via a one-off Cloud Run job or local proxy to Cloud SQL.

---

## GitHub Actions (alternative to local gcloud)

Push to `main` with these repository secrets:

- `GCP_PROJECT_ID`, `GCP_REGION`
- `GCP_WORKLOAD_IDENTITY_PROVIDER`, `GCP_SERVICE_ACCOUNT`
- `STRIPE_PRICE_COHORT`, `STRIPE_PRICE_STARTER`, `DEMO_USER_PASSWORD`

Run infra + secrets once locally (or Cloud Shell) before first CI deploy. Workflow: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml).

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `gcloud not recognized` | Install Cloud SDK, restart terminal |
| `docker daemon not running` | Start Docker Desktop |
| Cloud Run 500 on boot | Check logs: `gcloud run services logs read venturelens-web --region us-central1` |
| DB connection failed | Confirm Cloud SQL instance exists; IAM `cloudsql.client` on compute SA |
| Stripe checkout blank | Set `STRIPE_KEY=pk_test_...` in `.env`, re-run `secrets` + `web` |
| HTTPS / redirect loops | `trustProxies` enabled in `bootstrap/app.php` for Cloud Run |

---

## Teardown (save credits)

```bash
gcloud run services delete venturelens-web venturelens-worker --region us-central1 --quiet
gcloud sql instances delete venturelens --quiet
```

Keep Artifact Registry and secrets if you plan to redeploy.
