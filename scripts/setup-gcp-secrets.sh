#!/usr/bin/env bash
# Upload VentureLens secrets from local .env to GCP Secret Manager
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^(APP_KEY|GEMINI_API_KEY|STRIPE_|DB_PASSWORD=|GCP_DB_PASSWORD=|GCP_|GOOGLE_CLOUD_|QDRANT_)' .env | sed 's/\r$//')
  set +a
fi

PROJECT="${GCP_PROJECT_ID:-${GOOGLE_CLOUD_PROJECT_ID:-}}"
REGION="${GCP_REGION:-us-central1}"

if [[ -z "$PROJECT" ]]; then
  echo "Set GCP_PROJECT_ID in .env" >&2
  exit 1
fi

set_gcp_secret() {
  local name="$1"
  local value="$2"
  if [[ -z "$value" ]]; then
    echo "Skipping empty secret: $name" >&2
    return
  fi
  if gcloud secrets describe "$name" --project="$PROJECT" &>/dev/null; then
    echo "Updating secret $name..."
    printf '%s' "$value" | gcloud secrets versions add "$name" --project="$PROJECT" --data-file=-
  else
    echo "Creating secret $name..."
    printf '%s' "$value" | gcloud secrets create "$name" \
      --project="$PROJECT" --replication-policy=automatic --data-file=-
  fi
}

echo "Project: $PROJECT  Region: $REGION"
gcloud config set project "$PROJECT" --quiet

set_gcp_secret "venturelens-app-key" "${APP_KEY:-}"
set_gcp_secret "gemini-api-key" "${GEMINI_API_KEY:-}"
set_gcp_secret "stripe-secret" "${STRIPE_SECRET:-}"
set_gcp_secret "venturelens-db-password" "${GCP_DB_PASSWORD:-${DB_PASSWORD:-}}"

if [[ -n "${STRIPE_WEBHOOK_SECRET:-}" ]]; then
  set_gcp_secret "stripe-webhook-secret" "$STRIPE_WEBHOOK_SECRET"
else
  echo "STRIPE_WEBHOOK_SECRET empty — storing placeholder" >&2
  set_gcp_secret "stripe-webhook-secret" "whsec_configure_after_deploy"
fi

if [[ "${STRIPE_KEY:-}" == pk_* ]]; then
  set_gcp_secret "stripe-key" "$STRIPE_KEY"
else
  echo "STRIPE_KEY is not pk_* — set pk_test_ in .env for Stripe.js" >&2
fi

if [[ -n "${QDRANT_API_KEY:-}" ]]; then
  set_gcp_secret "qdrant-api-key" "$QDRANT_API_KEY"
fi

echo ""
echo "Done. Price IDs passed at deploy: STRIPE_PRICE_COHORT=${STRIPE_PRICE_COHORT:-} STRIPE_PRICE_STARTER=${STRIPE_PRICE_STARTER:-}"
echo "  RAG_VECTOR_STORE=${RAG_VECTOR_STORE:-mysql}"
