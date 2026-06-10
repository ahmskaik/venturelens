<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Agents\GrowthAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunGrowthAgentJob implements ShouldQueue
{
    use Queueable;

    public function handle(GrowthAgent $agent): void
    {
        $result = $agent->run();

        Log::info('agent.growth.completed', [
            'decision' => $result->decision,
            'action' => $result->actionTaken,
            'autonomy' => $result->autonomyLevel,
        ]);
    }
}
