<?php

namespace Tests\Unit;

use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiKeyPool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class GeminiClientRetryTest extends TestCase
{
    public function test_retries_on_429_then_succeeds(): void
    {
        Log::spy();

        Http::fake([
            '*' => Http::sequence()
                ->push(['error' => ['message' => 'Resource exhausted']], 429, ['Retry-After' => '1'])
                ->push([
                    'candidates' => [['content' => ['parts' => [['text' => '{"ok":true}']]]]],
                    'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 5],
                ], 200),
        ]);

        $client = new GeminiClient(GeminiKeyPool::single('test-key'), timeoutSeconds: 5, maxRetries: 3);

        $result = $client->generateContent('gemini-2.5-flash', 'sys', 'user');

        $this->assertSame('{"ok":true}', $result['content']);
        Log::shouldHaveReceived('warning')->with('gemini.api_retry', \Mockery::type('array'));
    }

    public function test_does_not_retry_limit_zero_quota(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Quota exceeded for metric: limit: 0']], 429),
        ]);

        $client = new GeminiClient(GeminiKeyPool::single('test-key'), timeoutSeconds: 5, maxRetries: 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('limit: 0');

        $client->generateContent('gemini-2.5-flash', 'sys', 'user');
    }

    public function test_rejects_deprecated_model(): void
    {
        $client = new GeminiClient(GeminiKeyPool::single('test-key'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('gemini-2.0-flash');

        $client->generateContent('gemini-2.0-flash', 'sys', 'user');
    }

    public function test_rotates_to_second_key_on_free_tier_quota(): void
    {
        Log::spy();

        $quotaMessage = 'You exceeded your current quota, please check your plan. '
            .'Quota exceeded for metric: generativelanguage.googleapis.com/generate_content_free_tier_requests, limit: 20';

        Http::fake([
            '*key=key-one*' => Http::response(['error' => ['message' => $quotaMessage]], 429),
            '*key=key-two*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => '{"ok":true}']]]]],
                'usageMetadata' => ['promptTokenCount' => 1, 'candidatesTokenCount' => 1],
            ], 200),
        ]);

        $pool = new GeminiKeyPool(['key-one', 'key-two'], rotationEnabled: true, quotaCooldownSeconds: 120);
        $client = new GeminiClient($pool, timeoutSeconds: 5, maxRetries: 2);

        $result = $client->generateContent('gemini-2.5-flash', 'sys', 'user');

        $this->assertSame('{"ok":true}', $result['content']);
        Log::shouldHaveReceived('warning')->with('gemini.key_rotated', \Mockery::type('array'));
    }
}
