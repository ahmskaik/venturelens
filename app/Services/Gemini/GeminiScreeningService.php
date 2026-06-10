<?php

namespace App\Services\Gemini;

use App\Models\Application;
use App\Models\Rubric;
use App\Models\ScreeningResult;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiScreeningService implements GeminiScreeningServiceInterface
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly PromptBuilder $promptBuilder,
    ) {}

    public function screenApplication(Application $application, Rubric $rubric, string $documentsSummary = ''): ScreeningResult
    {
        $model = config('services.gemini.models.flash', 'gemini-2.5-flash');
        $prompts = $this->promptBuilder->buildScreeningPrompt($application, $rubric, $documentsSummary);

        try {
            $response = $this->client->generateContent(
                model: $model,
                systemPrompt: $prompts['system'],
                userPrompt: $prompts['user'],
            );

            $parsed = $this->parseJsonResponse($response['content']);

            return ScreeningResult::create([
                'application_id' => $application->id,
                'model' => $model,
                'overall_score' => $parsed['overall_score'] ?? null,
                'criterion_scores' => $parsed['criterion_scores'] ?? [],
                'strengths' => $parsed['strengths'] ?? [],
                'weaknesses' => $parsed['weaknesses'] ?? [],
                'risk_flags' => $parsed['risk_flags'] ?? [],
                'summary' => $parsed['summary'] ?? null,
                'recommendation' => $parsed['recommendation'] ?? null,
                'raw_response' => [
                    'parsed' => $parsed,
                    'gemini' => $response['raw'],
                ],
                'prompt_tokens' => $response['prompt_tokens'],
                'completion_tokens' => $response['completion_tokens'],
                'latency_ms' => $response['latency_ms'],
            ]);
        } catch (RuntimeException $e) {
            Log::error('gemini.screening_failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return ScreeningResult::create([
                'application_id' => $application->id,
                'model' => $model,
                'error' => $e->getMessage(),
                'raw_response' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $content): array
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON for screening result.');
        }

        return $decoded;
    }
}
