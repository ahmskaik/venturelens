<?php

namespace App\Services\Agents;

use App\Models\Organization;
use App\Models\SupportRequest;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SupportAgent implements BusinessAgentInterface
{
    public function __construct(
        private readonly GeminiClient $client,
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
        $knowledge = $this->knowledgeBase();

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are the VentureLens Support Agent. Answer customer questions using the knowledge base. Decide whether to resolve or escalate to a human. Return JSON only.',
                userPrompt: json_encode([
                    'knowledge_base' => $knowledge,
                    'customer_question' => [
                        'subject' => $request->subject,
                        'question' => $request->question,
                        'organization' => $organization->name,
                        'plan' => $organization->plan,
                    ],
                    'output_schema' => [
                        'answer' => 'string',
                        'decision' => 'resolve|escalate',
                        'confidence' => 'number 0-1',
                        'reason' => 'string',
                    ],
                ], JSON_PRETTY_PRINT),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed)) {
                throw new RuntimeException('Invalid support agent JSON');
            }

            $confidence = (float) ($parsed['confidence'] ?? 0);
            $decision = $parsed['decision'] ?? 'escalate';
            $threshold = (float) config('venturelens.agents.support.auto_reply_confidence', 0.85);
            $autoResolve = $decision === 'resolve' && $confidence >= $threshold;

            $request->update([
                'ai_response' => $parsed['answer'] ?? null,
                'confidence' => $confidence,
                'status' => $autoResolve ? 'answered' : 'escalated',
                'autonomy_level' => $autoResolve ? 3 : 1,
            ]);

            $result = new AgentResult(
                decision: $autoResolve ? 'auto_resolve' : 'escalate_to_human',
                actionTaken: $autoResolve
                    ? "Auto-answered support request #{$request->id}"
                    : "Escalated support request #{$request->id} for human review",
                autonomyLevel: $autoResolve ? 3 : 1,
                confidence: $confidence,
                humanMinutesSaved: $autoResolve ? 15 : 5,
                metadata: [
                    'support_request_id' => $request->id,
                    'reason' => $parsed['reason'] ?? null,
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

    /**
     * @return list<string>
     */
    private function knowledgeBase(): array
    {
        return [
            'VentureLens screens startup applications using Google Gemini against configurable rubrics.',
            'Free trial includes 5 screenings. Cohort package ($199) includes 50 screenings. Starter ($299/mo) includes 200 screenings/month.',
            'Applications are screened asynchronously via a queue after submission.',
            'Pitch decks (PDF) are extracted and sent to Gemini for evaluation.',
            'Upgrade at Billing page. Manage subscription via Stripe customer portal.',
            'Support email: noreply@venturelens.app. Demo login: demo@venturelens.app',
        ];
    }
}
