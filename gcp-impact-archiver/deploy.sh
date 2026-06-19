#!/usr/bin/env bash
# VentureLens Nightly Impact Archive Deployment Script (Gen 2 Cloud Function)
set -eo pipefail

# Configuration (Edit or override via environment variables)
GCP_PROJECT_ID="${GCP_PROJECT_ID:-venturelens-499513}"
GCP_REGION="${GCP_REGION:-us-central1}"
GCS_BUCKET="${GCS_BUCKET:-venturelens-uploads-venturelens-499513}"
FUNCTION_NAME="${FUNCTION_NAME:-impact-archiver}"
SCHEDULER_JOB_NAME="${SCHEDULER_JOB_NAME:-nightly-impact-archival}"
ENTRY_POINT="archive_impact"

# Service Account Names
CF_SA_NAME="impact-archiver-sa"
SCHEDULER_SA_NAME="impact-scheduler-sa"

CF_SA_EMAIL="${CF_SA_NAME}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"
SCHEDULER_SA_EMAIL="${SCHEDULER_SA_NAME}@${GCP_PROJECT_ID}.iam.gserviceaccount.com"

echo "================================================================="
echo " Deploying VentureLens Nightly GCS Impact Archiver"
echo "================================================================="
echo "Project ID:      $GCP_PROJECT_ID"
echo "Region:          $GCP_REGION"
echo "Bucket:          $GCS_BUCKET"
echo "Function Name:   $FUNCTION_NAME"
echo "Scheduler Job:   $SCHEDULER_JOB_NAME"
echo "================================================================="

# Set gcloud project context
echo "Setting gcloud project context to: $GCP_PROJECT_ID..."
gcloud config set project "$GCP_PROJECT_ID"

# 1. Enable required APIs
echo "Enabling required GCP APIs (Cloud Functions, Run, Scheduler, Storage, IAM)..."
gcloud services enable \
  cloudfunctions.googleapis.com \
  run.googleapis.com \
  cloudscheduler.googleapis.com \
  storage.googleapis.com \
  iam.googleapis.com

# 2. Create Cloud Function Service Account if it does not exist
if ! gcloud iam service-accounts describe "$CF_SA_EMAIL" >/dev/null 2>&1; then
  echo "Creating Cloud Function Service Account: $CF_SA_EMAIL..."
  gcloud iam service-accounts create "$CF_SA_NAME" \
    --description="Service account for VentureLens Nightly GCS Impact Archive Cloud Function" \
    --display-name="VentureLens Impact Archiver SA"
else
  echo "Cloud Function Service Account already exists."
fi

# 3. Create Cloud Scheduler Service Account if it does not exist
if ! gcloud iam service-accounts describe "$SCHEDULER_SA_EMAIL" >/dev/null 2>&1; then
  echo "Creating Cloud Scheduler Service Account: $SCHEDULER_SA_EMAIL..."
  gcloud iam service-accounts create "$SCHEDULER_SA_NAME" \
    --description="Service account for Cloud Scheduler to invoke the Impact Archiver" \
    --display-name="VentureLens Impact Scheduler SA"
else
  echo "Cloud Scheduler Service Account already exists."
fi

# 4. Assign Storage Object Creator permission to the Function SA on the Bucket
echo "Assigning storage.objects.create permission on bucket gs://$GCS_BUCKET..."
gcloud storage buckets add-iam-policy-binding "gs://$GCS_BUCKET" \
  --member="serviceAccount:$CF_SA_EMAIL" \
  --role="roles/storage.objectCreator" \
  --quiet

# 5. Deploy Cloud Function (Gen 2)
echo "Deploying Gen 2 Cloud Function: $FUNCTION_NAME..."
gcloud functions deploy "$FUNCTION_NAME" \
  --gen2 \
  --runtime=python311 \
  --region="$GCP_REGION" \
  --entry-point="$ENTRY_POINT" \
  --trigger-http \
  --no-allow-unauthenticated \
  --service-account="$CF_SA_EMAIL" \
  --set-env-vars GCS_BUCKET="$GCS_BUCKET",IMPACT_API_URL="https://venturelens.app/api/v1/impact.json"

# 6. Retrieve function URL
echo "Retrieving deployed function URL..."
FUNCTION_URL=$(gcloud functions describe "$FUNCTION_NAME" --gen2 --region="$GCP_REGION" --format="value(serviceConfig.uri)")
echo "Function URL: $FUNCTION_URL"

# 7. Grant Scheduler Service Account permissions to invoke the Cloud Run service backing the function
# Note: Gen 2 functions run on Cloud Run, so we grant roles/run.invoker on the service.
echo "Granting invoker permissions to Scheduler Service Account on Cloud Run service..."
gcloud run services add-iam-policy-binding "$FUNCTION_NAME" \
  --region="$GCP_REGION" \
  --member="serviceAccount:$SCHEDULER_SA_EMAIL" \
  --role="roles/run.invoker" \
  --quiet

# 8. Create or Update Cloud Scheduler job
echo "Creating/Updating Cloud Scheduler Job..."
if gcloud scheduler jobs describe "$SCHEDULER_JOB_NAME" --location="$GCP_REGION" >/dev/null 2>&1; then
  echo "Updating existing Scheduler job..."
  gcloud scheduler jobs update http "$SCHEDULER_JOB_NAME" \
    --schedule="0 2 * * *" \
    --uri="$FUNCTION_URL" \
    --http-method=POST \
    --oidc-service-account-email="$SCHEDULER_SA_EMAIL" \
    --location="$GCP_REGION" \
    --time-zone="UTC"
else
  echo "Creating new Scheduler job..."
  gcloud scheduler jobs create http "$SCHEDULER_JOB_NAME" \
    --schedule="0 2 * * *" \
    --uri="$FUNCTION_URL" \
    --http-method=POST \
    --oidc-service-account-email="$SCHEDULER_SA_EMAIL" \
    --location="$GCP_REGION" \
    --time-zone="UTC"
fi

echo "================================================================="
echo " Nightly Impact Archive Deployment Completed Successfully!"
echo "================================================================="
echo "Function status: ACTIVE"
echo "Trigger URL:     $FUNCTION_URL"
echo "Scheduler run:   Daily at 02:00 UTC"
echo "To test manually, run:"
echo "gcloud scheduler jobs run $SCHEDULER_JOB_NAME --location=$GCP_REGION"
echo "================================================================="
