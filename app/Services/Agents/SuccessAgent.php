<?php

namespace App\Services\Agents;

use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\SuccessOutreachDraft;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SuccessAgent implements BusinessAgentInterface
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly AgentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'success';
    }

    public function run(): AgentResult
    {
        return new AgentResult(
            decision: 'idle',
            actionTaken: 'Success agent runs on Stripe payment events',
            autonomyLevel: 0,
            status: 'completed',
        );
    }

    public function recordPayment(Organization $organization, RevenueCharge $charge): ?AgentResult
    {
        if (! $this->registry->isEnabled($this->name())) {
            return null;
        }

        if ($this->alreadyLogged($charge)) {
            return null;
        }

        $owner = $organization->users()->wherePivot('role', 'owner')->first();

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are the VentureLens Success Agent. Draft a warm, professional email asking a paying customer for a short public testimonial about their experience screening applications. Never pressure them. Include opt-out. Return JSON only.',
                userPrompt: json_encode([
                    'customer' => [
                        'organization' => $organization->name,
                        'owner_name' => $owner?->name,
                        'plan' => $charge->plan,
                        'amount_usd' => round($charge->amount_cents / 100, 2),
                    ],
                    'output_schema' => [
                        'subject' => 'string',
                        'body' => 'string',
                        'confidence' => 'number 0-1',
                    ],
                ], JSON_PRETTY_PRINT),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed)) {
                throw new RuntimeException('Invalid success agent JSON');
            }

            $draft = SuccessOutreachDraft::create([
                'organization_id' => $organization->id,
                'revenue_charge_id' => $charge->id,
                'subject' => $parsed['subject'] ?? 'Quick favor — share your VentureLens experience?',
                'body' => $parsed['body'] ?? '',
                'status' => 'draft',
                'autonomy_level' => 1,
                'metadata' => [
                    'confidence' => $parsed['confidence'] ?? 0.8,
                    'prompt_tokens' => $response['prompt_tokens'],
                    'completion_tokens' => $response['completion_tokens'],
                ],
            ]);

            $result = new AgentResult(
                decision: 'draft_testimonial_request',
                actionTaken: sprintf(
                    'Drafted testimonial request email for %s plan payment (draft #%d — owner review required)',
                    $charge->plan,
                    $draft->id
                ),
                autonomyLevel: 1,
                confidence: (float) ($parsed['confidence'] ?? 0.8),
                humanMinutesSaved: 15,
                metadata: [
                    'success_outreach_draft_id' => $draft->id,
                    'revenue_charge_id' => $charge->id,
                ],
            );

            $this->registry->logExecution($organization, $this, $result, step: 'testimonial_request_drafted');

            return $result;
        } catch (RuntimeException $e) {
            Log::warning('success_agent.failed', [
                'organization_id' => $organization->id,
                'charge_id' => $charge->id,
                'error' => $e->getMessage(),
            ]);

            $draft = SuccessOutreachDraft::create([
                'organization_id' => $organization->id,
                'revenue_charge_id' => $charge->id,
                'subject' => 'How is VentureLens working for your program?',
                'body' => "Hi {$owner?->name},\n\nThank you for upgrading to VentureLens. If you're open to it, we'd love a short testimonial about your screening experience.\n\nBest,\nVentureLens Team",
                'status' => 'draft',
                'autonomy_level' => 1,
                'metadata' => ['fallback' => true, 'error' => $e->getMessage()],
            ]);

            $result = new AgentResult(
                decision: 'draft_testimonial_request',
                actionTaken: "Drafted fallback testimonial request (draft #{$draft->id})",
                autonomyLevel: 1,
                status: 'completed',
                metadata: ['success_outreach_draft_id' => $draft->id],
            );

            $this->registry->logExecution($organization, $this, $result, step: 'testimonial_request_drafted');

            return $result;
        }
    }

    private function alreadyLogged(RevenueCharge $charge): bool
    {
        return SuccessOutreachDraft::where('revenue_charge_id', $charge->id)->exists();
    }
}
