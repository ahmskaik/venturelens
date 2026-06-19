<?php

namespace Tests\Unit;

use App\Services\Gemini\GeminiKeyPool;
use RuntimeException;
use Tests\TestCase;

class GeminiKeyPoolTest extends TestCase
{
    public function test_single_key_mode_has_no_rotation(): void
    {
        $pool = GeminiKeyPool::single('only-key');

        $this->assertFalse($pool->isRotationEnabled());
        $this->assertSame(1, $pool->keyCount());
        $this->assertSame('only-key', $pool->nextKey()['key']);
    }

    public function test_parses_comma_and_whitespace_separated_keys(): void
    {
        $keys = GeminiKeyPool::parseKeysList(" key-a , key-b\nkey-c ");

        $this->assertSame(['key-a', 'key-b', 'key-c'], $keys);
    }

    public function test_round_robin_skips_keys_on_cooldown(): void
    {
        $pool = new GeminiKeyPool(['a', 'b', 'c'], rotationEnabled: true, quotaCooldownSeconds: 60);

        $first = $pool->nextKey();
        $pool->markQuotaExceeded($first['index']);

        $second = $pool->nextKey();

        $this->assertNotSame($first['key'], $second['key']);
    }

    public function test_from_config_uses_single_key_when_pool_disabled(): void
    {
        config([
            'services.gemini.api_key' => 'primary-key',
            'services.gemini.api_keys' => 'extra-one,extra-two',
            'services.gemini.key_pool_enabled' => false,
        ]);

        $pool = GeminiKeyPool::fromConfig();

        $this->assertFalse($pool->isRotationEnabled());
        $this->assertSame(1, $pool->keyCount());
        $this->assertSame('primary-key', $pool->nextKey()['key']);
    }

    public function test_from_config_merges_keys_when_pool_enabled(): void
    {
        config([
            'services.gemini.api_key' => 'primary-key',
            'services.gemini.api_keys' => 'extra-one,extra-two',
            'services.gemini.key_pool_enabled' => true,
            'services.gemini.key_pool_quota_cooldown' => 30,
        ]);

        $pool = GeminiKeyPool::fromConfig();

        $this->assertTrue($pool->isRotationEnabled());
        $this->assertSame(3, $pool->keyCount());
    }

    public function test_throws_when_no_keys_configured(): void
    {
        config([
            'services.gemini.api_key' => '',
            'services.gemini.api_keys' => '',
            'services.gemini.key_pool_enabled' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY is not configured');

        GeminiKeyPool::fromConfig();
    }
}
