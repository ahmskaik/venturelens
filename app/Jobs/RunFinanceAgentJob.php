<?php

namespace App\Jobs;

use App\Services\Agents\FinanceAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunFinanceAgentJob implements ShouldQueue
{
    use Queueable;

    public function handle(FinanceAgent $agent): void
    {
        $result = $agent->run();

        Log::info('agent.finance.completed', [
            'decision' => $result->decision,
            'action' => $result->actionTaken,
            'autonomy' => $result->autonomyLevel,
        ]);
    }
}
