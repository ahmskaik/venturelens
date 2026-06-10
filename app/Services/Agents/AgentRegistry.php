<?php

namespace App\Services\Agents;

use App\Models\AgentExecution;
use App\Models\BusinessAgent;
use App\Models\Organization;

class AgentRegistry
{
    public function isEnabled(string $agentName): bool
    {
        $agent = BusinessAgent::where('name', $agentName)->first();

        return $agent?->isEnabled() ?? true;
    }

    public function dailyCap(string $agentName): int
    {
        return (int) (BusinessAgent::where('name', $agentName)->value('daily_action_cap')
            ?? config("venturelens.agents.{$agentName}.daily_cap", 50));
    }

    public function actionsToday(string $agentName): int
    {
        return AgentExecution::where('agent_name', $agentName)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->count();
    }

    public function canRun(string $agentName): bool
    {
        return $this->isEnabled($agentName)
            && $this->actionsToday($agentName) < $this->dailyCap($agentName);
    }

    public function logExecution(
        Organization $organization,
        BusinessAgentInterface $agent,
        AgentResult $result,
        ?string $step = null,
    ): void {
        app(\App\Services\AgentExecutionLogger::class)->log(
            organization: $organization,
            step: $step ?? $agent->name().'_action',
            agentName: $agent->name(),
            decision: $result->decision,
            actionTaken: $result->actionTaken,
            autonomyLevel: $result->autonomyLevel,
            confidence: $result->confidence,
            humanMinutesSaved: $result->humanMinutesSaved,
            status: $result->status,
            metadata: $result->metadata,
        );
    }
}
