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

rag_env_vars() {
  local store="${RAG_VECTOR_STORE:-mysql}"
  store="$(echo "$store" | tr '[:upper:]' '[:lower:]')"
  if [[ "$store" != "mysql" && "$store" != "qdrant" ]]; then
    echo "Warning: invalid RAG_VECTOR_STORE=$store — using mysql" >&2
    store="mysql"
  fi
  local vars="RAG_VECTOR_STORE=${store}"
  vars+=",GEMINI_EMBEDDING_MODEL=${GEMINI_EMBEDDING_MODEL:-gemini-embedding-001}"
  vars+=",RAG_EMBEDDING_DIMENSIONS=${RAG_EMBEDDING_DIMENSIONS:-768}"
  if [[ "$store" == "qdrant" ]]; then
    if [[ -z "${QDRANT_URL:-}" ]]; then
      echo "Error: RAG_VECTOR_STORE=qdrant requires QDRANT_URL in .env" >&2
      exit 1
    fi
    vars+=",QDRANT_URL=${QDRANT_URL}"
    vars+=",QDRANT_COLLECTION=${QDRANT_COLLECTION:-venturelens_rag}"
  fi
  echo "$vars"
}

rag_secrets() {
  local store="${RAG_VECTOR_STORE:-mysql}"
  store="$(echo "$store" | tr '[:upper:]' '[:lower:]')"
  if [[ "$store" == "qdrant" && -n "${QDRANT_API_KEY:-}" ]]; then
    echo "QDRANT_API_KEY=qdrant-api-key:latest"
  fi
}

stripe_env_vars() {
  local vars="STRIPE_PRICE_COHORT=${STRIPE_PRICE_COHORT:-}"
  vars+=",STRIPE_PRICE_STARTER=${STRIPE_PRICE_STARTER:-}"
  vars+=",DEMO_USER_EMAIL=${DEMO_USER_EMAIL:-demo@venturelens.app}"
  vars+=",DEMO_USER_PASSWORD=${DEMO_USER_PASSWORD:-demo123}"
  vars+=",GEMINI_MAX_RETRIES=${GEMINI_MAX_RETRIES:-5}"
  vars+=",$(rag_env_vars)"
  vars+=",SESSION_DRIVER=database,CACHE_STORE=database"
  if [[ "${STRIPE_KEY:-}" == pk_* ]]; then
    vars+=",STRIPE_KEY=${STRIPE_KEY}"
  fi
  echo "$vars"
}

base_env_vars() {
  echo "APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,QUEUE_CONNECTION=database,DB_CONNECTION=mysql,DB_SOCKET=/cloudsql/${SQL_INSTANCE},DB_DATABASE=venturelens,DB_USERNAME=venturelens,GEMINI_MODEL_FLASH=gemini-2.5-flash,FILESYSTEM_UPLOADS_DISK=gcs,GOOGLE_CLOUD_PROJECT_ID=${PROJECT},GOOGLE_CLOUD_STORAGE_BUCKET=venturelens-uploads-${PROJECT},$(stripe_env_vars)"
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
  local rag_secret
  rag_secret="$(rag_secrets)"
  if [[ -n "$rag_secret" ]]; then
    secrets+=",${rag_secret}"
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
    --set-env-vars "$(base_env_vars),RUN_MIGRATIONS=true,RUN_SEED=true" \
    --set-secrets "${secrets}"
}

set_app_url() {
  local url
  url="$(gcloud run services describe "${SERVICE_WEB}" --region "${REGION}" --format='value(status.url)')"
  gcloud run services update "${SERVICE_WEB}" --region "${REGION}" \
    --update-env-vars "APP_URL=${url}" --quiet
  echo "${url}"
}

deploy_worker() {
  local secrets="APP_KEY=venturelens-app-key:latest,GEMINI_API_KEY=gemini-api-key:latest,DB_PASSWORD=venturelens-db-password:latest,STRIPE_SECRET=stripe-secret:latest"
  local rag_secret
  rag_secret="$(rag_secrets)"
  if [[ -n "$rag_secret" ]]; then
    secrets+=",${rag_secret}"
  fi
  gcloud run deploy "${SERVICE_WORKER}" \
    --image "${IMAGE_URI}" \
    --region "${REGION}" \
    --platform managed \
    --no-allow-unauthenticated \
    --no-cpu-throttling \
    --memory 1Gi \
    --cpu 1 \
    --min-instances 1 \
    --max-instances 3 \
    --add-cloudsql-instances "${SQL_INSTANCE}" \
    --set-env-vars "$(base_env_vars),CONTAINER_ROLE=worker" \
    --set-secrets "${secrets}"
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
      source <(grep -E '^(STRIPE_|DEMO_|GEMINI_MAX|DB_PASSWORD|GCP_|RAG_|QDRANT_)' .env | sed 's/\r$//')
      set +a
    fi
    if [[ -x scripts/setup-gcp-infra.sh ]]; then
      bash scripts/setup-gcp-infra.sh
    fi
    if [[ -x scripts/setup-gcp-secrets.sh ]]; then
      bash scripts/setup-gcp-secrets.sh
    fi
    build
    deploy_web
    deploy_worker
    url="$(set_app_url)"
    echo ""
    echo "Deployed. Web URL: ${url}"
    echo "  Impact: ${url}/impact"
    echo "  Health: ${url}/up"
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
