<?php

namespace App\Jobs;

use App\Models\SupportRequest;
use App\Services\Agents\SupportAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunSupportAgentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ?int $supportRequestId = null,
    ) {}

    public function handle(SupportAgent $agent): void
    {
        if ($this->supportRequestId) {
            $request = SupportRequest::find($this->supportRequestId);
            if ($request) {
                $result = $agent->processRequest($request);
                Log::info('agent.support.processed', ['id' => $request->id, 'decision' => $result->decision]);

                return;
            }
        }

        $result = $agent->run();
        Log::info('agent.support.completed', ['decision' => $result->decision]);
    }
}
