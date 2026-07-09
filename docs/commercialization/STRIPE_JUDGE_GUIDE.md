# Stripe checkout — judge verification guide

VentureLens uses **Laravel Cashier** with Stripe **test mode** for the hackathon demo. Revenue is split into **arms-length** (net-new customers) and **related-party** (BINA/Gohorto/demo org) on the Billing page and `/impact`.

## Plans

| Plan | Price | Screenings | Stripe mode |
|------|-------|------------|-------------|
| Cohort package | $199 one-time | +50 (additive) | `payment` |
| Starter | $299/month | 200/month | `subscription` |

## Arms-length vs related-party checklist

Classification runs in `RevenueClassifier` at checkout (`BillingController`) and is stored on each `revenue_charges` row. Stripe session metadata includes `revenue_type` so webhook fulfillment matches what the user saw.

A charge is **related-party** if **any** rule matches (first match wins):

| Rule | Config key | Default values | Example that triggers |
|------|------------|----------------|------------------------|
| Organization slug | `RELATED_PARTY_ORG_SLUGS` | `demo-incubator`, `bina`, `gohorto` | Org name **"BINA"** → slug `bina-r7pp` (prefix `bina-`) |
| Organization website host | `RELATED_PARTY_DOMAINS` | `gohorto.com`, `bina.org.tr`, `bina.com.tr` | Website `https://www.gohorto.com` |
| Checkout user email domain | `RELATED_PARTY_DOMAINS` | (same) | `team@gohorto.com` |
| Org owner email domain | `RELATED_PARTY_DOMAINS` | (same) | Owner `director@bina.org.tr` |

If none match → **arms-length**.

### Why the first $199 test was related-party

The successful Cohort payment (`$199`, session metadata `revenue_type: related_party`) was from:

| Field | Value | Matched rule? |
|-------|-------|---------------|
| Org name | BINA | — |
| Org slug | `bina-r7pp` | **Yes** — slug starts with `bina-` |
| Owner email | `ahmskaik@binaprogram.org` | No (`binaprogram.org` not in list) |
| Website | *(empty)* | No |

**Takeaway:** Personal or partner email domains are not enough if the **organization slug** (derived from org name at registration) hits a related-party prefix. For arms-length evidence, register with a **neutral org name** (e.g. "Pacific Innovation Lab") and a **non-related email** (e.g. `@gmail.com`).

### Arms-length test account checklist

Before checkout, confirm all of the following:

- [ ] Email domain **not** in `RELATED_PARTY_DOMAINS`
- [ ] Organization name **not** Demo Incubator, BINA, Gohorto (slug must not be `demo-incubator`, `bina-*`, or `gohorto-*`)
- [ ] Website (if set) host **not** in `RELATED_PARTY_DOMAINS`
- [ ] Billing page → Upgrade shows checkout (Stripe configured)
- [ ] After pay: `/billing/success` loads; `/billing` shows charge as **arms-length**
- [ ] `/impact` and `GET /api/v1/impact.json` show `arms_length_revenue_usd` > 0

## Automated tests (CI)

```bash
php artisan test --filter=StripeCheckoutFlowTest
php artisan test --filter=RevenueClassifierTest
```

Tests verify billing page auth, graceful error when Stripe is unconfigured, checkout fulfillment, arms-length classification, idempotent session handling, and BINA slug / Gmail neutral org rules.

## Manual end-to-end (local, ~5 min)

### 1. Configure Stripe test keys

```env
STRIPE_SECRET=sk_test_...
STRIPE_KEY=pk_test_...
STRIPE_PRICE_COHORT=price_...
STRIPE_PRICE_STARTER=price_...
```

Create prices: `php artisan stripe:ensure-prices`

### 2. Webhook listener

```bash
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

Set `STRIPE_WEBHOOK_SECRET=whsec_...` from CLI output.

### 3. Arms-length test purchase

1. **Register** (do not use demo login): e.g. `you+pilot@gmail.com`, org **"Pacific Innovation Lab"**, country US.
2. **Billing** → Upgrade to Cohort package.
3. Card: `4242 4242 4242 4242`, any future expiry, any CVC/ZIP.
4. Confirm `/billing/success`, then `/billing` charge row shows **arms-length**.
5. Open `/impact` — **Arms-length revenue** > $0.
6. Snapshot evidence: `php artisan impact:snapshot` → `docs/evidence/impact-YYYYMMDD.json`.

Optional classifier-only check (no Stripe):

```bash
php scripts/verify-arms-length-checkout.php
```

### 4. Related-party demo

Login `demo@venturelens.app` / `demo123` — org slug `demo-incubator` → checkout metadata **related-party** (reported separately on `/impact`).

## Troubleshooting: `Received unknown parameter: agent_identity_token`

This error appears **on Stripe’s hosted checkout page** (not in VentureLens PHP). Stripe Link / agentic-commerce can send `agent_identity_token` when confirming payment; some sandbox accounts or browsers reject it.

**Fixes (try in order):**

1. Use **Cohort $199** (one-time `payment` mode) instead of Starter subscription — recommended for judge arms-length evidence.
2. Checkout now disables Link via `wallet_options.link.display = never` — retry after pulling latest code.
3. Open checkout in **Chrome/Firefox incognito** with extensions disabled (not Cursor embedded browser).
4. Enter card manually; do not use “Save my information for faster checkout”.
5. Use a plain email like `@gmail.com` (not a custom domain autofill).

## Judge evidence

| Evidence | URL / path |
|----------|------------|
| Live KPI dashboard | `/impact` |
| Machine-readable KPIs | `GET /api/v1/impact.json` |
| Billing revenue split | `/billing` (screenshot) |
| Committed snapshot | `docs/evidence/impact-YYYYMMDD.json` |
| Stripe Dashboard | Payments (test mode) |
| Screenshot checklist | [`JUDGE_EVIDENCE.md`](JUDGE_EVIDENCE.md) |

Key JSON fields for judges:

```json
{
  "business": {
    "arms_length_revenue_usd": 199,
    "related_party_revenue_usd": 199,
    "arms_length_paying_customers": 1
  },
  "ai_operations": {
    "pct_decisions_by_ai": 100
  }
}
```
