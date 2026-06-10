# Stripe checkout — judge verification guide

VentureLens uses **Laravel Cashier** with Stripe **test mode** for the hackathon demo. Revenue is split into **arms-length** (net-new customers) and **related-party** (BINA/Gohorto/demo org) on the Billing page and `/impact`.

## Plans

| Plan | Price | Screenings | Stripe mode |
|------|-------|------------|-------------|
| Cohort package | $199 one-time | +50 (additive) | `payment` |
| Starter | $299/month | 200/month | `subscription` |

## Automated tests (CI)

```bash
php artisan test --filter=StripeCheckoutFlowTest
```

Tests verify billing page auth, graceful error when Stripe is unconfigured, checkout fulfillment, arms-length classification, and idempotent session handling.

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

1. Register a new org (email not on related-party list).
2. Billing → Upgrade to Cohort package.
3. Card: `4242 4242 4242 4242`.
4. Confirm `/billing/success` and **arms-length** on `/impact`.

### 4. Related-party demo

Login `demo@venturelens.app` — checkout shows **related-party** (reported separately).

## Judge evidence

- `/billing` revenue split screenshot
- Stripe Dashboard payments
- `GET /api/v1/impact.json` → `business.arms_length_revenue_usd`
