<?php

namespace App\Services;

use App\Models\AgentExecution;
use App\Models\Application;
use App\Models\Organization;

class AgentExecutionLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        Organization $organization,
        string $step,
        ?Application $application = null,
        ?string $agentName = 'screening',
        ?string $decision = null,
        ?string $actionTaken = null,
        int $autonomyLevel = 3,
        ?float $confidence = null,
        ?int $humanMinutesSaved = null,
        string $status = 'completed',
        ?array $metadata = null,
    ): AgentExecution {
        return AgentExecution::create([
            'organization_id' => $organization->id,
            'application_id' => $application?->id,
            'agent_name' => $agentName,
            'step' => $step,
            'decision' => $decision,
            'action_taken' => $actionTaken,
            'autonomy_level' => $autonomyLevel,
            'confidence' => $confidence,
            'human_minutes_saved' => $humanMinutesSaved,
            'status' => $status,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
