<?php

namespace App\Services\Agents;

class AgentResult
{
    public function __construct(
        public readonly string $decision,
        public readonly string $actionTaken,
        public readonly int $autonomyLevel,
        public readonly ?float $confidence = null,
        public readonly ?int $humanMinutesSaved = null,
        public readonly array $metadata = [],
        public readonly string $status = 'completed',
    ) {}
}
