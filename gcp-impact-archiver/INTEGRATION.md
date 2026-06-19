# Integration Guide: Nightly Impact Archive to GCS

## 1. What was built
We built a standalone **GCP Cloud Function (Gen 2, Python 3.11)** that acts as an automated audit trail for hackathon judges. Triggered nightly at **02:00 UTC** by **Cloud Scheduler**, the function calls the VentureLens public API endpoint `GET https://venturelens.app/api/v1/impact.json`, validates the JSON metrics schema, parses the date, and uploads the snapshot format directly to the VentureLens GCS bucket as `evidence/impact-YYYYMMDD.json`. This implements category impact audit trails with zero modification or runtime impact on the main Laravel monorepo.

---

## 2. Files and Target Paths
The tool is packaged in a standalone subdirectory `gcp-impact-archiver/` in the root of the repository:
```
gcp-impact-archiver/
├── .env.example
├── LICENSE
├── README.md
├── INTEGRATION.md
├── main.py
├── requirements.txt
├── test_main.py
├── deploy.sh
└── deploy.ps1
```
No modifications to Laravel files are required. You can merge the directory directly into the main repository.

---

## 3. GCP Resources Created
Deployment provisions the following GCP resources:

| Resource Type | Resource Name | Region | Description |
|---|---|---|---|
| **Cloud Function (Gen 2)** | `impact-archiver` | `us-central1` | Runs `main.py` Python runtime |
| **Cloud Scheduler Job** | `nightly-impact-archival` | `us-central1` | Triggers function daily at 02:00 UTC |
| **IAM Service Account** | `impact-archiver-sa` | Global | Service account for CF with GCS upload permissions |
| **IAM Service Account** | `impact-scheduler-sa` | Global | Service account for Scheduler with OIDC execution token |
| **GCS Bucket Folder** | `gs://venturelens-uploads-venturelens-499513/evidence/` | `us-central1` | Target folder for archived JSON files |

---

## 4. Environment Variables
No secrets are stored in the code. The Cloud Function configures runtime details using the following environment variables (defined during deployment or inside Cloud Console):

- **`GCS_BUCKET`**: `venturelens-uploads-venturelens-499513` (The target bucket to store the impact snapshots).
- **`IMPACT_API_URL`**: `https://venturelens.app/api/v1/impact.json` (The URL to fetch the metrics from).

---

## 5. How Judges / Developers Verify It Works

### Step 1: Run the trigger manually
Run this CLI command to invoke the scheduler job immediately:
```bash
gcloud scheduler jobs run nightly-impact-archival --location=us-central1
```

### Step 2: Check function execution logs
View logs from the Cloud Function to ensure HTTP retrieval, validation, and storage upload all succeeded:
```bash
gcloud beta run services logs read impact-archiver --region=us-central1 --limit=30
```
*Expected log output pattern:*
```
INFO:impact-archiver:Starting impact archive task.
INFO:impact-archiver:Target API URL: https://venturelens.app/api/v1/impact.json
INFO:impact-archiver:Target GCS Bucket: venturelens-uploads-venturelens-499513
INFO:impact-archiver:Validation successful. Extracted date: 20260617. GCS Destination path: evidence/impact-20260617.json
INFO:impact-archiver:Successfully archived impact metrics to gs://venturelens-uploads-venturelens-499513/evidence/impact-20260617.json
```

### Step 3: Check GCS Bucket contents
Ensure the snapshot file is stored correctly in the bucket:
```bash
gcloud storage ls gs://venturelens-uploads-venturelens-499513/evidence/
```
Output will list the archived snapshot files:
```
gs://venturelens-uploads-venturelens-499513/evidence/impact-20260617.json
```
To verify the content matches, download and check the schema:
```bash
gcloud storage cat gs://venturelens-uploads-venturelens-499513/evidence/impact-20260617.json
```
