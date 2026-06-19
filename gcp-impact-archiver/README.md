# VentureLens - Nightly Impact Archive to GCS (Task A)

A standalone Google Cloud Function (Gen 2, Python 3.11) designed to run nightly via Cloud Scheduler. It fetches VentureLens's public impact metrics, validates the JSON data structure, parses the generation date, and archives the snapshot to a Google Cloud Storage (GCS) bucket.

This provides an automated, immutable evidence trail of the platform's actual impact and AI-native operations for hackathon judges and stakeholders.

## Features
- **Stateless HTTP Trigger**: Exposed as a secure, unauthenticated-blocked Gen 2 Cloud Function (using `functions-framework`).
- **Data Integrity Validation**: Ensures the fetched JSON contains required metrics (`generated_at`, `business`, `activity`, `ai_operations`, `impact`) and holds valid structures.
- **Dynamic File Naming**: Parses `generated_at` to construct a file path under `/evidence/impact-YYYYMMDD.json`.
- **GCP Native Security**: Authenticated HTTP trigger using OIDC and a dedicated Service Account with scoped Storage access (`roles/storage.objectCreator`).
- **Double Deployable**: Supports deployment via Linux/macOS bash (`deploy.sh`) and Windows PowerShell (`deploy.ps1`).

---

## Local Development & Testing

### 1. Prerequisites
- Python 3.11+
- [Google Cloud SDK (gcloud CLI)](https://cloud.google.com/sdk/docs/install) authenticated to your target project.

### 2. Setup environment
Create a virtual environment and install dependencies:
```bash
python -m venv venv
# On Unix:
source venv/bin/activate
# On Windows (PowerShell):
.\venv\Scripts\Activate.ps1

pip install -r requirements.txt
```

### 3. Run unit tests
We use Python's built-in `unittest` framework with mock assertions to run unit tests without making actual network calls:
```bash
python -m unittest test_main.py
```

### 4. Local execution simulation
To run the function locally on port `8080`:
```bash
# Copy env file
cp .env.example .env

# Start the functions framework local server
functions-framework --target=archive_impact --debug
```

In another terminal, trigger the function:
```bash
curl -X POST http://localhost:8080
```
*Note: Local execution uploading to GCS requires active GCP credentials in your environment. You can set the path to a credentials JSON file in `GOOGLE_APPLICATION_CREDENTIALS` in your `.env` or authenticate your terminal using `gcloud auth application-default login`.*

---

## Deployment (GCP)

Make sure you are authenticated with the GCP project:
```bash
gcloud auth login
gcloud config set project venturelens-499513
```

### Option A: Unix / macOS / Cloud Shell
Make the script executable and run:
```bash
chmod +x deploy.sh
./deploy.sh
```

### Option B: Windows PowerShell
Run the deployment script from PowerShell:
```powershell
.\deploy.ps1
```

### What gets deployed
- **Cloud Function**: Gen 2 function `impact-archiver` running Python 3.11 in region `us-central1`.
- **Service Accounts**:
  - `impact-archiver-sa`: Dedicated service account with storage object creation writes on the GCS bucket.
  - `impact-scheduler-sa`: Cloud Scheduler service account with OIDC token authorization to trigger the function.
- **Cloud Scheduler Job**: `nightly-impact-archival` scheduled at `02:00 UTC` daily.

---

## Manual Verification
Once deployed, you can verify execution:

1. **Trigger via Cloud Scheduler**:
   ```bash
   gcloud scheduler jobs run nightly-impact-archival --location=us-central1
   ```
2. **Check Cloud Function Logs**:
   ```bash
   gcloud beta run services logs read impact-archiver --region=us-central1 --limit=20
   ```
3. **Verify GCS Storage Archive**:
   Check the bucket to see if the file `evidence/impact-YYYYMMDD.json` was created:
   ```bash
   gcloud storage ls gs://venturelens-uploads-venturelens-499513/evidence/
   ```

---

## License
MIT License. See [LICENSE](LICENSE) for details.
