<?php

namespace App\Services\Agents;

use App\Models\Organization;
use App\Models\SupportRequest;
use App\Services\Chat\ProjectRagChatService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SupportAgent implements BusinessAgentInterface
{
    public function __construct(
        private readonly ProjectRagChatService $ragChat,
        private readonly AgentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'support';
    }

    public function run(): AgentResult
    {
        if (! $this->registry->canRun($this->name())) {
            $result = new AgentResult(
                decision: 'rate_limited',
                actionTaken: 'Support agent daily cap reached or agent disabled',
                autonomyLevel: 0,
                status: 'failed',
            );
            $this->logFailure(Organization::first(), $result, 'support_rate_limited');

            return $result;
        }

        $request = SupportRequest::where('status', 'open')->oldest()->first();

        if (! $request) {
            return new AgentResult(
                decision: 'no_open_tickets',
                actionTaken: 'No open support requests to process',
                autonomyLevel: 0,
                status: 'completed',
            );
        }

        return $this->processRequest($request);
    }

    private function logFailure(?Organization $organization, AgentResult $result, string $step): void
    {
        if ($organization) {
            $this->registry->logExecution($organization, $this, $result, step: $step);
        }
    }

    public function processRequest(SupportRequest $request): AgentResult
    {
        $organization = $request->organization;

        try {
            $rag = $this->ragChat->answerForSupportTicket(
                $organization,
                $request->question,
                $request->program_id,
            );

            $threshold = (float) config('venturelens.agents.support.auto_reply_confidence', 0.65);
            $autoResolve = ! $rag['escalate'] && $rag['confidence'] >= $threshold;

            $request->update([
                'ai_response' => $rag['answer'],
                'confidence' => $rag['confidence'],
                'sources' => $rag['sources'],
                'status' => $autoResolve ? 'answered' : 'escalated',
                'autonomy_level' => $autoResolve ? 3 : 1,
            ]);

            $result = new AgentResult(
                decision: $autoResolve ? 'auto_resolve' : 'escalate_to_human',
                actionTaken: $autoResolve
                    ? "RAG answered support request #{$request->id}"
                    : "RAG answered with review flag for support request #{$request->id}",
                autonomyLevel: $autoResolve ? 3 : 1,
                confidence: $rag['confidence'],
                humanMinutesSaved: $autoResolve ? 15 : 5,
                metadata: [
                    'support_request_id' => $request->id,
                    'source_count' => count($rag['sources']),
                ],
            );

            $this->registry->logExecution($organization, $this, $result, step: 'support_ticket_handled');

            return $result;
        } catch (RuntimeException $e) {
            Log::warning('support_agent.failed', ['error' => $e->getMessage(), 'request_id' => $request->id]);

            $request->update(['status' => 'escalated']);

            $result = new AgentResult(
                decision: 'gemini_error',
                actionTaken: 'Support agent failed — ticket escalated to human',
                autonomyLevel: 0,
                status: 'failed',
                metadata: ['support_request_id' => $request->id, 'error' => $e->getMessage()],
            );
            $this->registry->logExecution($organization, $this, $result, step: 'support_gemini_error');

            return $result;
        }
    }
}
