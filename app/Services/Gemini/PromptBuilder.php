<?php

namespace App\Services\Gemini;

use App\Models\Application;
use App\Models\Rubric;
use App\Support\ApplicationProfile;

class PromptBuilder
{
    private const MAX_DOCUMENT_CHARS = 30_000;

    public function buildScreeningPrompt(Application $application, Rubric $rubric, string $documentsSummary = ''): array
    {
        $system = 'You are an expert startup evaluator for innovation programs. Score applications fairly, explainably, and consistently. '
            .'Every criterion score and overall_score must be on a 0–100 scale (not 1–10). '
            .'Evaluate content in its original language. Return summary in English unless the application is clearly in Arabic or Turkish — then provide summary in that language. '
            .'Return valid JSON only matching the output_schema.';

        $applicationPayload = [
            'startup_name' => $application->startup_name,
            'founder_name' => $application->founder_name,
            'founder_email' => $application->founder_email,
            'country_code' => $application->country_code,
            'stage' => $application->stage,
            'sector' => $application->sector,
            'fields' => ApplicationProfile::flattenForScreening($application->form_data),
        ];

        $outputSchema = [
            'overall_score' => 'number 0-100',
            'criterion_scores' => [
                ['name' => 'string', 'score' => 'number 0-100', 'reasoning' => 'string'],
            ],
            'strengths' => ['string'],
            'weaknesses' => ['string'],
            'risk_flags' => [
                ['code' => 'string', 'severity' => 'low|medium|high', 'message' => 'string'],
            ],
            'summary' => 'string',
            'recommendation' => 'shortlist|reject|needs_review',
            'completeness' => 'complete|incomplete',
            'missing_fields' => ['string'],
        ];

        $user = json_encode([
            'rubric' => [
                'name' => $rubric->name,
                'criteria' => $rubric->criteria,
            ],
            'application' => $applicationPayload,
            'documents_summary' => $this->truncateDocuments($documentsSummary),
            'output_schema' => $outputSchema,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return [
            'system' => $system,
            'user' => $user,
        ];
    }

    private function truncateDocuments(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_DOCUMENT_CHARS) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_DOCUMENT_CHARS)."\n\n[Document truncated for token limits]";
    }
}
