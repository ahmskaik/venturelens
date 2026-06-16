#!/usr/bin/env bash
# First-time GCP infrastructure for VentureLens
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^(GCP_|GOOGLE_CLOUD_|DB_PASSWORD=|GCP_DB_PASSWORD=)' .env | sed 's/\r$//')
  set +a
fi

PROJECT="${GCP_PROJECT_ID:-${GOOGLE_CLOUD_PROJECT_ID:-}}"
REGION="${GCP_REGION:-us-central1}"
SQL_INSTANCE="venturelens"
DB_NAME="venturelens"
DB_USER="venturelens"
ARTIFACT_REPO="venturelens"
SKIP_SQL="${SKIP_SQL:-0}"

if [[ -z "$PROJECT" ]]; then
  echo "Set GCP_PROJECT_ID in .env" >&2
  exit 1
fi

echo "VentureLens GCP bootstrap — project=$PROJECT region=$REGION"
gcloud config set project "$PROJECT" --quiet

echo "Enabling APIs..."
gcloud services enable \
  run.googleapis.com,sqladmin.googleapis.com,secretmanager.googleapis.com, \
  artifactregistry.googleapis.com,iam.googleapis.com,cloudresourcemanager.googleapis.com \
  --project="$PROJECT" --quiet

if ! gcloud artifacts repositories describe "$ARTIFACT_REPO" --location="$REGION" --project="$PROJECT" &>/dev/null; then
  gcloud artifacts repositories create "$ARTIFACT_REPO" \
    --repository-format=docker \
    --location="$REGION" \
    --project="$PROJECT" \
    --description="VentureLens container images"
fi

BUCKET_NAME="venturelens-uploads-${PROJECT}"
if ! gcloud storage buckets describe "gs://${BUCKET_NAME}" --project="$PROJECT" &>/dev/null; then
  echo "Creating Cloud Storage bucket ${BUCKET_NAME}..."
  gcloud storage buckets create "gs://${BUCKET_NAME}" \
    --location="$REGION" \
    --project="$PROJECT" \
    --uniform-bucket-level-access
fi

if [[ "$SKIP_SQL" != "1" ]]; then
  CLOUD_DB_PASSWORD="${GCP_DB_PASSWORD:-${DB_PASSWORD:-}}"
  if [[ -z "$CLOUD_DB_PASSWORD" ]]; then
    echo "Set GCP_DB_PASSWORD in .env" >&2
    exit 1
  fi

  if ! gcloud sql instances describe "$SQL_INSTANCE" --project="$PROJECT" &>/dev/null; then
    echo "Creating Cloud SQL (db-f1-micro, ~10 min)..."
    gcloud sql instances create "$SQL_INSTANCE" \
      --database-version=MYSQL_8_0 \
      --tier=db-f1-micro \
      --region="$REGION" \
      --project="$PROJECT" \
      --storage-size=10GB \
      --storage-auto-increase \
      --root-password="$CLOUD_DB_PASSWORD"
  fi

  gcloud sql databases create "$DB_NAME" --instance="$SQL_INSTANCE" --project="$PROJECT" 2>/dev/null || true
  if ! gcloud sql users describe "$DB_USER" --instance="$SQL_INSTANCE" --project="$PROJECT" &>/dev/null; then
    gcloud sql users create "$DB_USER" --instance="$SQL_INSTANCE" --password="$CLOUD_DB_PASSWORD" --project="$PROJECT"
  else
    gcloud sql users set-password "$DB_USER" --instance="$SQL_INSTANCE" --password="$CLOUD_DB_PASSWORD" --project="$PROJECT"
  fi
fi

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
COMPUTE_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"

for role in roles/cloudsql.client roles/secretmanager.secretAccessor roles/storage.objectAdmin; do
  gcloud projects add-iam-policy-binding "$PROJECT" \
    --member="serviceAccount:${COMPUTE_SA}" \
    --role="$role" --quiet 2>/dev/null || true
done

echo ""
echo "Bootstrap complete. Next: ./scripts/deploy-cloud-run.sh deploy"
