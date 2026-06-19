<?php

namespace App\Services\Gemini;

use RuntimeException;

/**
 * Round-robin Gemini API keys with per-key cooldown after free-tier quota (429).
 *
 * When key_pool_enabled=false, behaves as a single-key pool (normal billing mode).
 */
class GeminiKeyPool
{
    private int $nextIndex = 0;

    /** @var array<int, float> Unix timestamps — key index => available after */
    private array $cooldownUntil = [];

    /**
     * @param  list<string>  $keys
     */
    public function __construct(
        private readonly array $keys,
        private readonly bool $rotationEnabled = false,
        private readonly int $quotaCooldownSeconds = 60,
    ) {
        if ($keys === []) {
            throw new RuntimeException('GeminiKeyPool requires at least one API key.');
        }
    }

    public static function fromConfig(): self
    {
        $primary = trim((string) config('services.gemini.api_key'));
        $poolEnabled = (bool) config('services.gemini.key_pool_enabled', false);
        $listed = self::parseKeysList((string) config('services.gemini.api_keys', ''));

        if ($poolEnabled) {
            $keys = self::uniqueKeys(array_merge(
                $listed,
                $primary !== '' ? [$primary] : [],
            ));
        } else {
            $keys = $primary !== '' ? [$primary] : self::uniqueKeys($listed);
        }

        if ($keys === []) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        return new self(
            keys: $keys,
            rotationEnabled: $poolEnabled && count($keys) > 1,
            quotaCooldownSeconds: max(1, (int) config('services.gemini.key_pool_quota_cooldown', 60)),
        );
    }

    public static function single(string $apiKey): self
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key cannot be empty.');
        }

        return new self([$apiKey], rotationEnabled: false);
    }

    public function isRotationEnabled(): bool
    {
        return $this->rotationEnabled;
    }

    public function keyCount(): int
    {
        return count($this->keys);
    }

    public function hasAvailableKey(): bool
    {
        $now = microtime(true);

        foreach ($this->keys as $index => $_key) {
            if (($this->cooldownUntil[$index] ?? 0) <= $now) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{index: int, key: string}
     */
    public function nextKey(): array
    {
        $count = count($this->keys);
        $now = microtime(true);
        $bestIndex = null;
        $bestWait = PHP_FLOAT_MAX;

        for ($offset = 0; $offset < $count; $offset++) {
            $index = ($this->nextIndex + $offset) % $count;
            $availableAt = $this->cooldownUntil[$index] ?? 0;

            if ($availableAt <= $now) {
                $this->nextIndex = ($index + 1) % $count;

                return ['index' => $index, 'key' => $this->keys[$index]];
            }

            $wait = $availableAt - $now;
            if ($wait < $bestWait) {
                $bestWait = $wait;
                $bestIndex = $index;
            }
        }

        $index = $bestIndex ?? 0;
        $this->nextIndex = ($index + 1) % $count;

        return ['index' => $index, 'key' => $this->keys[$index]];
    }

    public function markQuotaExceeded(int $index): void
    {
        if (! $this->rotationEnabled) {
            return;
        }

        $this->cooldownUntil[$index] = microtime(true) + $this->quotaCooldownSeconds;
    }

    /**
     * @return list<string>
     */
    public static function parseKeysList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        return self::uniqueKeys(is_array($parts) ? $parts : []);
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function uniqueKeys(array $keys): array
    {
        $seen = [];
        $unique = [];

        foreach ($keys as $key) {
            $key = trim($key);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $key;
        }

        return $unique;
    }
}
