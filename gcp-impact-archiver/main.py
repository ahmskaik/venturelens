import logging
import os
import re
import json
import requests
import functions_framework
from google.cloud import storage

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("impact-archiver")

# Environment defaults
DEFAULT_BUCKET = "venturelens-uploads-venturelens-499513"
DEFAULT_API_URL = "https://venturelens.app/api/v1/impact.json"


def validate_impact_json(data):
    """
    Validates the structure of the retrieved impact JSON.
    Returns (is_valid, error_message).
    """
    required_keys = ["generated_at", "business", "activity", "ai_operations", "impact"]
    
    # Check top-level keys
    for key in required_keys:
        if key not in data:
            return False, f"Missing required top-level key: '{key}'"
            
    # Check that generated_at is a string and looks like a timestamp
    gen_at = data.get("generated_at")
    if not isinstance(gen_at, str) or not gen_at:
        return False, "Field 'generated_at' must be a non-empty string"
        
    # Basic ISO 8601 validation (starts with YYYY-MM-DD)
    if not re.match(r"^\d{4}-\d{2}-\d{2}", gen_at):
        return False, f"Field 'generated_at' ('{gen_at}') does not start with YYYY-MM-DD pattern"
        
    # Check that business/activity/ai_operations/impact are objects
    for obj_key in ["business", "activity", "ai_operations", "impact"]:
        if not isinstance(data.get(obj_key), dict):
            return False, f"Field '{obj_key}' must be a JSON object (dict)"
            
    return True, None


def extract_date_suffix(generated_at):
    """
    Extracts YYYYMMDD suffix from ISO8601 timestamp string (e.g., '2026-06-11T08:50:16+00:00').
    """
    # Grab the date portion (first 10 chars: YYYY-MM-DD)
    date_part = generated_at[:10]
    # Strip hyphens
    return date_part.replace("-", "")


@functions_framework.http
def archive_impact(request):
    """
    HTTP Cloud Function to fetch the nightly impact JSON, validate it,
    and archive it to Google Cloud Storage.
    
    Triggered by Cloud Scheduler HTTP request.
    """
    # 1. Determine configuration from environment
    api_url = os.environ.get("IMPACT_API_URL", DEFAULT_API_URL)
    gcs_bucket_name = os.environ.get("GCS_BUCKET", DEFAULT_BUCKET)
    
    logger.info(f"Starting impact archive task.")
    logger.info(f"Target API URL: {api_url}")
    logger.info(f"Target GCS Bucket: {gcs_bucket_name}")
    
    # 2. Fetch the JSON from the VentureLens API
    try:
        response = requests.get(api_url, timeout=15)
        if response.status_code != 200:
            err_msg = f"Failed to fetch impact metrics. HTTP Status Code: {response.status_code}"
            logger.error(err_msg)
            return err_msg, 502
    except requests.RequestException as e:
        err_msg = f"Network exception occurred while fetching metrics: {str(e)}"
        logger.error(err_msg)
        return err_msg, 502

    # 3. Parse JSON
    try:
        payload = response.json()
    except json.JSONDecodeError as e:
        err_msg = f"Failed to parse response payload as JSON: {str(e)}"
        logger.error(err_msg)
        return err_msg, 400

    # 4. Validate JSON structure
    is_valid, validation_err = validate_impact_json(payload)
    if not is_valid:
        err_msg = f"Impact JSON validation failed: {validation_err}"
        logger.error(err_msg)
        return err_msg, 400
        
    # 5. Extract date for filename suffix
    gen_at = payload.get("generated_at")
    date_suffix = extract_date_suffix(gen_at)
    gcs_path = f"evidence/impact-{date_suffix}.json"
    
    logger.info(f"Validation successful. Extracted date: {date_suffix}. GCS Destination path: {gcs_path}")

    # 6. Upload to Google Cloud Storage
    try:
        storage_client = storage.Client()
        bucket = storage_client.bucket(gcs_bucket_name)
        blob = bucket.blob(gcs_path)
        
        # Serialize with formatting to match committed JSON evidence files style
        json_content = json.dumps(payload, indent=4)
        
        # Upload content, setting content type to application/json
        blob.upload_from_string(json_content, content_type="application/json")
        
        success_msg = f"Successfully archived impact metrics to gs://{gcs_bucket_name}/{gcs_path}"
        logger.info(success_msg)
        return success_msg, 200
        
    except Exception as e:
        err_msg = f"Failed to upload snapshot to GCS: {str(e)}"
        logger.error(err_msg)
        return err_msg, 500
