#!/usr/bin/env bash
# VentureLens Cloud Run deploy helper
set -euo pipefail

PROJECT="${GCP_PROJECT_ID:?Set GCP_PROJECT_ID}"
REGION="${GCP_REGION:-us-central1}"
IMAGE_NAME="${IMAGE_NAME:-venturelens/app}"
SERVICE_WEB="${SERVICE_WEB:-venturelens-web}"
SERVICE_WORKER="${SERVICE_WORKER:-venturelens-worker}"
IMAGE_URI="${REGION}-docker.pkg.dev/${PROJECT}/${IMAGE_NAME}:latest"
SQL_INSTANCE="${SQL_INSTANCE:-${PROJECT}:${REGION}:venturelens}"

stripe_env_vars() {
  local vars="STRIPE_PRICE_COHORT=${STRIPE_PRICE_COHORT:-}"
  vars+=",STRIPE_PRICE_STARTER=${STRIPE_PRICE_STARTER:-}"
  vars+=",DEMO_USER_EMAIL=${DEMO_USER_EMAIL:-demo@venturelens.app}"
  vars+=",DEMO_USER_PASSWORD=${DEMO_USER_PASSWORD:-demo-password-change-me}"
  vars+=",GEMINI_MAX_RETRIES=${GEMINI_MAX_RETRIES:-5}"
  vars+=",SESSION_DRIVER=database,CACHE_STORE=database"
  if [[ "${STRIPE_KEY:-}" == pk_* ]]; then
    vars+=",STRIPE_KEY=${STRIPE_KEY}"
  fi
  echo "$vars"
}

base_env_vars() {
  echo "APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,QUEUE_CONNECTION=database,DB_CONNECTION=mysql,DB_SOCKET=/cloudsql/${SQL_INSTANCE},DB_DATABASE=venturelens,DB_USERNAME=venturelens,GEMINI_MODEL_FLASH=gemini-2.5-flash,$(stripe_env_vars)"
}

cmd="${1:-help}"

build() {
  echo "Building ${IMAGE_URI}..."
  gcloud auth configure-docker "${REGION}-docker.pkg.dev" --quiet
  docker build -f docker/Dockerfile -t "${IMAGE_URI}" .
  docker push "${IMAGE_URI}"
  echo "Pushed ${IMAGE_URI}"
}

deploy_web() {
  local secrets="APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest,STRIPE_WEBHOOK_SECRET=stripe-webhook-secret:latest"
  if [[ "${STRIPE_KEY:-}" == pk_* ]]; then
    secrets+=",STRIPE_KEY=stripe-key:latest"
  fi
  gcloud run deploy "${SERVICE_WEB}" \
    --image "${IMAGE_URI}" \
    --region "${REGION}" \
    --platform managed \
    --allow-unauthenticated \
    --port 8080 \
    --memory 1Gi \
    --cpu 1 \
    --min-instances 0 \
    --max-instances 10 \
    --add-cloudsql-instances "${SQL_INSTANCE}" \
    --set-env-vars "$(base_env_vars),RUN_MIGRATIONS=true" \
    --set-secrets "${secrets}"
}

deploy_worker() {
  gcloud run deploy "${SERVICE_WORKER}" \
    --image "${IMAGE_URI}" \
    --region "${REGION}" \
    --platform managed \
    --no-allow-unauthenticated \
    --memory 1Gi \
    --cpu 1 \
    --min-instances 1 \
    --max-instances 3 \
    --add-cloudsql-instances "${SQL_INSTANCE}" \
    --set-env-vars "$(base_env_vars),CONTAINER_ROLE=worker" \
    --set-secrets "APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest"
}

case "${cmd}" in
  build)
    build
    ;;
  deploy)
    # Load Stripe price IDs from .env if present
    if [[ -f .env ]]; then
      set -a
      # shellcheck disable=SC1091
      source <(grep -E '^(STRIPE_|DEMO_|GEMINI_MAX)' .env | sed 's/\r$//')
      set +a
    fi
    build
    deploy_web
    deploy_worker
    echo ""
    echo "Web URL:"
    gcloud run services describe "${SERVICE_WEB}" --region "${REGION}" --format='value(status.url)'
    ;;
  web)
    deploy_web
    ;;
  worker)
    deploy_worker
    ;;
  *)
    echo "Usage: GCP_PROJECT_ID=... GCP_REGION=... $0 {build|deploy|web|worker}"
    exit 1
    ;;
esac
