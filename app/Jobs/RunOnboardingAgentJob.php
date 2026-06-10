<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\User;
use App\Services\Agents\OnboardingAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunOnboardingAgentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $organizationId,
        public readonly ?int $userId = null,
    ) {}

    public function handle(OnboardingAgent $agent): void
    {
        if ($this->organizationId === 0) {
            $agent->run();

            return;
        }

        $organization = Organization::find($this->organizationId);
        if (! $organization) {
            return;
        }

        $owner = $this->userId
            ? User::find($this->userId)
            : $organization->users()->wherePivot('role', 'owner')->first();

        $result = $agent->onboardOrganization($organization, $owner);

        Log::info('agent.onboarding.completed', [
            'organization_id' => $organization->id,
            'decision' => $result->decision,
            'action' => $result->actionTaken,
        ]);
    }
}
