import unittest
from unittest.mock import patch, MagicMock
import json
import requests

# Import functions from main
from main import validate_impact_json, extract_date_suffix, archive_impact


class TestImpactArchiver(unittest.TestCase):

    def setUp(self):
        # A valid baseline payload for testing
        self.valid_payload = {
            "generated_at": "2026-06-11T08:50:16+00:00",
            "assumptions": {
                "manual_review_minutes_per_app": 45,
                "avg_jobs_per_startup": 3
            },
            "business": {
                "arms_length_paying_customers": 3,
                "arms_length_revenue_usd": 697,
                "related_party_revenue_usd": 199,
                "total_revenue_usd": 896
            },
            "activity": {
                "applications_screened": 7,
                "gemini_api_calls": 7
            },
            "ai_operations": {
                "total_agent_actions": 79,
                "pct_decisions_by_ai": 88.6
            },
            "impact": {
                "founder_hours_saved": 5.3,
                "accepted_startups": 1
            }
        }

    def test_validate_impact_json_valid(self):
        is_valid, err = validate_impact_json(self.valid_payload)
        self.assertTrue(is_valid)
        self.assertIsNone(err)

    def test_validate_impact_json_missing_key(self):
        invalid_payload = self.valid_payload.copy()
        del invalid_payload["business"]
        is_valid, err = validate_impact_json(invalid_payload)
        self.assertFalse(is_valid)
        self.assertIn("Missing required top-level key: 'business'", err)

    def test_validate_impact_json_invalid_generated_at(self):
        invalid_payload = self.valid_payload.copy()
        invalid_payload["generated_at"] = "invalid-date-format"
        is_valid, err = validate_impact_json(invalid_payload)
        self.assertFalse(is_valid)
        self.assertIn("does not start with YYYY-MM-DD pattern", err)

    def test_validate_impact_json_invalid_type(self):
        invalid_payload = self.valid_payload.copy()
        invalid_payload["business"] = "should-be-a-dict-not-string"
        is_valid, err = validate_impact_json(invalid_payload)
        self.assertFalse(is_valid)
        self.assertIn("must be a JSON object", err)

    def test_extract_date_suffix(self):
        self.assertEqual(extract_date_suffix("2026-06-11T08:50:16+00:00"), "20260611")
        self.assertEqual(extract_date_suffix("2026-12-31"), "20261231")

    @patch("main.requests.get")
    @patch("main.storage.Client")
    def test_archive_impact_success(self, mock_storage_client_cls, mock_requests_get):
        # Configure requests mock
        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.return_value = self.valid_payload
        mock_requests_get.return_value = mock_response

        # Configure GCS mock
        mock_storage_client = MagicMock()
        mock_bucket = MagicMock()
        mock_blob = MagicMock()
        
        mock_storage_client_cls.return_value = mock_storage_client
        mock_storage_client.bucket.return_value = mock_bucket
        mock_bucket.blob.return_value = mock_blob

        # Run the function
        # mock_request parameter can be None since functions framework passes a request object
        # which we do not inspect in archive_impact except for internal env config
        response_text, status_code = archive_impact(None)

        # Assertions
        self.assertEqual(status_code, 200)
        self.assertIn("Successfully archived impact metrics", response_text)
        
        # Verify GCS calls
        mock_storage_client.bucket.assert_called_once_with("venturelens-uploads-venturelens-499513")
        mock_bucket.blob.assert_called_once_with("evidence/impact-20260611.json")
        
        # Verify upload content looks like JSON
        args, kwargs = mock_blob.upload_from_string.call_args
        uploaded_str = args[0]
        uploaded_json = json.loads(uploaded_str)
        self.assertEqual(uploaded_json["generated_at"], "2026-06-11T08:50:16+00:00")
        self.assertEqual(kwargs["content_type"], "application/json")

    @patch("main.requests.get")
    def test_archive_impact_http_error(self, mock_requests_get):
        # Mock API returning 500 Internal Server Error
        mock_response = MagicMock()
        mock_response.status_code = 500
        mock_requests_get.return_value = mock_response

        response_text, status_code = archive_impact(None)
        self.assertEqual(status_code, 502)
        self.assertIn("Failed to fetch impact metrics", response_text)

    @patch("main.requests.get")
    def test_archive_impact_request_exception(self, mock_requests_get):
        # Mock requests raising a ConnectionError
        mock_requests_get.side_effect = requests.RequestException("Connection timed out")

        response_text, status_code = archive_impact(None)
        self.assertEqual(status_code, 502)
        self.assertIn("Network exception occurred", response_text)

    @patch("main.requests.get")
    def test_archive_impact_json_decode_error(self, mock_requests_get):
        # Mock API returning non-JSON content
        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.side_effect = json.JSONDecodeError("Expecting value", "", 0)
        mock_requests_get.return_value = mock_response

        response_text, status_code = archive_impact(None)
        self.assertEqual(status_code, 400)
        self.assertIn("Failed to parse response payload as JSON", response_text)

    @patch("main.requests.get")
    def test_archive_impact_validation_error(self, mock_requests_get):
        # Mock API returning JSON that fails validation
        invalid_payload = self.valid_payload.copy()
        del invalid_payload["generated_at"]

        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.return_value = invalid_payload
        mock_requests_get.return_value = mock_response

        response_text, status_code = archive_impact(None)
        self.assertEqual(status_code, 400)
        self.assertIn("Impact JSON validation failed", response_text)

    @patch("main.requests.get")
    @patch("main.storage.Client")
    def test_archive_impact_gcs_error(self, mock_storage_client_cls, mock_requests_get):
        # Configure requests mock
        mock_response = MagicMock()
        mock_response.status_code = 200
        mock_response.json.return_value = self.valid_payload
        mock_requests_get.return_value = mock_response

        # Configure GCS mock to throw exception
        mock_storage_client = MagicMock()
        mock_storage_client_cls.return_value = mock_storage_client
        mock_storage_client.bucket.side_effect = Exception("Bucket permission denied")

        response_text, status_code = archive_impact(None)
        self.assertEqual(status_code, 500)
        self.assertIn("Failed to upload snapshot to GCS", response_text)


if __name__ == "__main__":
    unittest.main()
