<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** @var list<string> */
    private const DEPRECATED_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite',
    ];

    /** @var list<int> */
    private const RETRYABLE_STATUS_CODES = [429, 500, 502, 503, 504];

    public function __construct(
        private readonly GeminiKeyPool $keyPool,
        private readonly int $timeoutSeconds = 60,
        private readonly int $maxRetries = 5,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            keyPool: GeminiKeyPool::fromConfig(),
            timeoutSeconds: (int) config('services.gemini.timeout', 60),
            maxRetries: (int) config('services.gemini.max_retries', 5),
        );
    }

    /**
     * @param  array<string, mixed>  $generationConfig
     * @return array{content: string, prompt_tokens: int, completion_tokens: int, latency_ms: int, raw: array}
     */
    public function generateContent(
        string $model,
        string $systemPrompt,
        string $userPrompt,
        array $generationConfig = [],
        ?int $timeoutSeconds = null,
        ?int $maxRetries = null,
    ): array {
        if (in_array($model, self::DEPRECATED_MODELS, true)) {
            throw new RuntimeException(
                "Model {$model} was shut down on 2026-06-01. Set GEMINI_MODEL_FLASH=gemini-2.5-flash in your .env file."
            );
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => array_merge([
                'responseMimeType' => 'application/json',
                'temperature' => 0.2,
            ], $generationConfig),
        ];

        $attempt = 0;
        $lastException = null;
        $lastStatus = null;
        $timeout = $timeoutSeconds ?? $this->timeoutSeconds;
        $retries = $maxRetries ?? $this->maxRetries;
        $httpAttempts = 0;
        $maxHttpAttempts = $retries * max(1, $this->keyPool->keyCount());

        while ($httpAttempts < $maxHttpAttempts) {
            $httpAttempts++;
            ['index' => $keyIndex, 'key' => $apiKey] = $this->keyPool->nextKey();
            $url = sprintf('%s/%s:generateContent?key=%s', self::BASE_URL, $model, $apiKey);
            $started = microtime(true);

            try {
                /** @var Response $response */
                $response = Http::timeout($timeout)
                    ->post($url, $payload);

                $latencyMs = (int) round((microtime(true) - $started) * 1000);
                $lastStatus = $response->status();

                if ($response->failed()) {
                    throw $this->buildApiException($response, $model);
                }

                $body = $response->json();
                $text = data_get($body, 'candidates.0.content.parts.0.text');

                if (! is_string($text) || $text === '') {
                    throw new RuntimeException('Gemini returned empty content.');
                }

                $promptTokens = (int) data_get($body, 'usageMetadata.promptTokenCount', 0);
                $completionTokens = (int) data_get($body, 'usageMetadata.candidatesTokenCount', 0);

                if ($httpAttempts > 1) {
                    Log::info('gemini.api_call_recovered', [
                        'model' => $model,
                        'http_attempt' => $httpAttempts,
                        'latency_ms' => $latencyMs,
                        'key_index' => $keyIndex,
                        'key_pool' => $this->keyPool->isRotationEnabled(),
                    ]);
                }

                Log::info('gemini.api_call', [
                    'model' => $model,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'latency_ms' => $latencyMs,
                    'http_attempt' => $httpAttempts,
                    'key_index' => $keyIndex,
                    'key_pool' => $this->keyPool->isRotationEnabled(),
                ]);

                return [
                    'content' => $text,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'latency_ms' => $latencyMs,
                    'raw' => $body,
                ];
            } catch (RuntimeException $e) {
                $lastException = $e;
                $message = $e->getMessage();

                if ($this->isLimitZeroQuota($message)) {
                    break;
                }

                if ($this->isFreeTierQuotaExceeded($message) && $this->keyPool->isRotationEnabled()) {
                    $this->keyPool->markQuotaExceeded($keyIndex);

                    Log::warning('gemini.key_rotated', [
                        'model' => $model,
                        'key_index' => $keyIndex,
                        'key_count' => $this->keyPool->keyCount(),
                        'error' => mb_substr($message, 0, 200),
                    ]);

                    if ($this->keyPool->hasAvailableKey()) {
                        continue;
                    }
                }

                if (! $this->shouldRetry($e, $lastStatus)) {
                    break;
                }

                $attempt++;
                $delayMs = $this->retryDelayMilliseconds($e, $attempt, $lastStatus);

                Log::warning('gemini.api_retry', [
                    'model' => $model,
                    'attempt' => $attempt,
                    'max_retries' => $retries,
                    'delay_ms' => $delayMs,
                    'status' => $lastStatus,
                    'key_index' => $keyIndex,
                    'error' => $message,
                ]);

                usleep($delayMs * 1000);
            }
        }

        throw $lastException ?? new RuntimeException('Gemini API call failed.');
    }

    private function buildApiException(Response $response, string $model): RuntimeException
    {
        $status = $response->status();
        $body = $response->json();
        $message = data_get($body, 'error.message', $response->body());

        if ($status === 429 && str_contains((string) $message, 'limit: 0')) {
            return new RuntimeException(
                "Gemini quota unavailable for model {$model} (limit: 0). "
                .'This usually means the model is deprecated or your project has no free-tier quota. '
                .'Fix: set GEMINI_MODEL_FLASH=gemini-2.5-flash in .env. '
                .'If it persists, enable billing in Google AI Studio (see docs/commercialization/GEMINI_SETUP.md).'
            );
        }

        if (in_array($status, self::RETRYABLE_STATUS_CODES, true)) {
            $retryAfter = $response->header('Retry-After');

            return new RuntimeException(
                "Gemini transient error ({$status}): {$message}"
                .($retryAfter ? " [Retry-After: {$retryAfter}]" : '')
            );
        }

        return new RuntimeException("Gemini API error ({$status}): {$message}");
    }

    private function isLimitZeroQuota(string $message): bool
    {
        return str_contains($message, 'limit: 0');
    }

    private function isFreeTierQuotaExceeded(string $message): bool
    {
        return str_contains($message, 'exceeded your current quota')
            || str_contains($message, 'Quota exceeded for metric');
    }

    private function shouldRetry(RuntimeException $e, ?int $status): bool
    {
        $message = $e->getMessage();

        if ($this->isLimitZeroQuota($message)) {
            return false;
        }

        if ($this->isFreeTierQuotaExceeded($message)) {
            return false;
        }

        if ($status !== null && in_array($status, self::RETRYABLE_STATUS_CODES, true)) {
            return true;
        }

        return str_contains($message, '(429)')
            || str_contains($message, '(503)')
            || str_contains($message, '(502)')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'transient error');
    }

    private function retryDelayMilliseconds(RuntimeException $e, int $attempt, ?int $status): int
    {
        $delayMs = 500;

        if (preg_match('/\[Retry-After: (\d+)\]/', $e->getMessage(), $matches)) {
            $delayMs = ((int) $matches[1] * 1000) + random_int(250, 750);
        } elseif (preg_match('/retry in ([\d.]+)s/i', $e->getMessage(), $matches)) {
            $delayMs = (int) (floatval($matches[1]) * 1000) + random_int(250, 750);
        } else {
            $base = $status === 429 ? 1000 : 500;
            $delayMs = (int) (pow(2, $attempt - 1) * $base) + random_int(0, 500);
        }

        return min($delayMs, 8000);
    }
}
