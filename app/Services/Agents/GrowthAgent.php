<?php

namespace App\Services\Agents;

use App\Models\GrowthOutreachDraft;
use App\Models\Organization;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GrowthAgent implements BusinessAgentInterface
{
    /** @var list<array{org: string, country: string, focus: string}> */
    private const TARGETS = [
        ['org' => 'Istanbul University Innovation Hub', 'country' => 'TR', 'focus' => 'university incubator'],
        ['org' => 'MENA Startup Accelerator', 'country' => 'AE', 'focus' => 'regional accelerator'],
        ['org' => 'GreenTech Foundry', 'country' => 'GB', 'focus' => 'climate/sustainability cohort'],
    ];

    public function __construct(
        private readonly GeminiClient $client,
        private readonly AgentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'growth';
    }

    public function run(): AgentResult
    {
        if (! $this->registry->canRun($this->name())) {
            $result = new AgentResult(
                decision: 'rate_limited',
                actionTaken: 'Growth agent daily cap reached or agent disabled',
                autonomyLevel: 0,
                status: 'failed',
            );
            $this->logFailure($result, 'growth_rate_limited');

            return $result;
        }

        $target = self::TARGETS[array_rand(self::TARGETS)];
        $platformOrg = Organization::where('slug', 'demo-incubator')->first()
            ?? Organization::first();

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are the VentureLens Growth Agent. Draft warm, professional B2B outreach to incubator program directors. Never use spammy language. Include an opt-out line. Return JSON only.',
                userPrompt: json_encode([
                    'product' => 'VentureLens — AI-powered startup application screening with Gemini',
                    'target' => $target,
                    'output_schema' => [
                        'subject' => 'string',
                        'body' => 'string',
                        'personalization_hook' => 'string',
                        'confidence' => 'number 0-1',
                    ],
                ], JSON_PRETTY_PRINT),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed)) {
                throw new RuntimeException('Invalid growth agent JSON');
            }

            $draft = GrowthOutreachDraft::create([
                'target_organization' => $target['org'],
                'target_country' => $target['country'],
                'channel' => 'email',
                'subject' => $parsed['subject'] ?? 'VentureLens for your cohort',
                'body' => $parsed['body'] ?? '',
                'status' => 'draft',
                'autonomy_level' => 1,
                'metadata' => [
                    'personalization_hook' => $parsed['personalization_hook'] ?? null,
                    'prompt_tokens' => $response['prompt_tokens'],
                    'completion_tokens' => $response['completion_tokens'],
                ],
            ]);

            if ($platformOrg) {
                $this->registry->logExecution($platformOrg, $this, new AgentResult(
                    decision: 'draft_outreach',
                    actionTaken: "Drafted outreach to {$target['org']} (not sent — human review required)",
                    autonomyLevel: 1,
                    confidence: (float) ($parsed['confidence'] ?? 0.8),
                    humanMinutesSaved: 20,
                    metadata: ['draft_id' => $draft->id, 'target' => $target['org']],
                ), step: 'growth_outreach_drafted');
            }

            return new AgentResult(
                decision: 'draft_outreach',
                actionTaken: "Created outreach draft #{$draft->id} for {$target['org']}",
                autonomyLevel: 1,
                confidence: (float) ($parsed['confidence'] ?? 0.8),
                humanMinutesSaved: 20,
                metadata: ['draft_id' => $draft->id],
            );
        } catch (RuntimeException $e) {
            Log::warning('growth_agent.failed', ['error' => $e->getMessage()]);

            $result = new AgentResult(
                decision: 'gemini_error',
                actionTaken: 'Growth agent failed — safe default: no outreach sent',
                autonomyLevel: 0,
                status: 'failed',
                metadata: ['error' => $e->getMessage()],
            );
            $this->logFailure($result, 'growth_gemini_error');

            return $result;
        }
    }

    private function logFailure(AgentResult $result, string $step): void
    {
        $org = Organization::where('slug', 'demo-incubator')->first() ?? Organization::first();
        if ($org) {
            $this->registry->logExecution($org, $this, $result, step: $step);
        }
    }
}
