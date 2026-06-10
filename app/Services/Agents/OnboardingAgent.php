<?php

namespace App\Services\Agents;

use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class OnboardingAgent implements BusinessAgentInterface
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly AgentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'onboarding';
    }

    public function run(): AgentResult
    {
        if (! $this->registry->canRun($this->name())) {
            return new AgentResult(
                decision: 'rate_limited',
                actionTaken: 'Onboarding agent daily cap reached or agent disabled',
                autonomyLevel: 0,
                status: 'failed',
            );
        }

        $organization = Organization::query()
            ->whereDoesntHave('programs')
            ->oldest()
            ->first();

        if (! $organization) {
            return new AgentResult(
                decision: 'idle',
                actionTaken: 'All organizations have at least one program',
                autonomyLevel: 0,
                status: 'completed',
            );
        }

        $owner = $organization->users()->wherePivot('role', 'owner')->first();

        return $this->onboardOrganization($organization, $owner);
    }

    public function onboardOrganization(Organization $organization, ?User $owner = null): AgentResult
    {
        if (! $this->registry->isEnabled($this->name())) {
            return new AgentResult(
                decision: 'disabled',
                actionTaken: 'Onboarding agent is disabled',
                autonomyLevel: 0,
                status: 'failed',
            );
        }

        $rubric = $organization->rubrics()->where('is_default', true)->first()
            ?? $organization->rubrics()->first();

        if (! $rubric) {
            $rubric = Rubric::create([
                'organization_id' => $organization->id,
                'name' => 'General Startup Evaluation',
                'criteria' => Rubric::defaultCriteria(),
                'is_default' => true,
            ]);
        }

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are the VentureLens Onboarding Agent. Propose incubator-specific evaluation rubric criteria and an initial open cohort program. Be practical for early-stage startups. Return JSON only.',
                userPrompt: json_encode([
                    'organization' => [
                        'name' => $organization->name,
                        'country_code' => $organization->country_code,
                        'website' => $organization->website,
                        'owner_name' => $owner?->name,
                    ],
                    'output_schema' => [
                        'rubric_name' => 'string',
                        'criteria' => 'array of {name, weight, description, scoring_guide}',
                        'program' => [
                            'name' => 'string',
                            'slug' => 'string lowercase hyphenated',
                            'description' => 'string',
                            'max_applications' => 'integer',
                        ],
                        'confidence' => 'number 0-1',
                    ],
                ], JSON_PRETTY_PRINT),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed)) {
                throw new RuntimeException('Invalid onboarding agent JSON');
            }

            $criteria = $this->normalizeCriteria($parsed['criteria'] ?? Rubric::defaultCriteria());
            $rubric->update([
                'name' => $parsed['rubric_name'] ?? $rubric->name,
                'criteria' => $criteria,
            ]);

            $programData = $parsed['program'] ?? [];
            $slug = Str::slug($programData['slug'] ?? Str::slug($organization->name).'-cohort');
            if ($organization->programs()->where('slug', $slug)->exists()) {
                $slug .= '-'.Str::lower(Str::random(3));
            }

            $program = Program::create([
                'organization_id' => $organization->id,
                'name' => $programData['name'] ?? "{$organization->name} Cohort",
                'slug' => $slug,
                'description' => $programData['description'] ?? 'Applications evaluated with your AI-powered rubric.',
                'opens_at' => now(),
                'closes_at' => now()->addMonths(3),
                'max_applications' => (int) ($programData['max_applications'] ?? 100),
                'status' => 'open',
                'rubric_id' => $rubric->id,
            ]);

            $result = new AgentResult(
                decision: 'program_setup',
                actionTaken: sprintf(
                    'Configured rubric "%s" and opened program "%s" (/%s apply)',
                    $rubric->name,
                    $program->name,
                    $program->slug
                ),
                autonomyLevel: 2,
                confidence: (float) ($parsed['confidence'] ?? 0.85),
                humanMinutesSaved: 30,
                metadata: [
                    'program_id' => $program->id,
                    'rubric_id' => $rubric->id,
                    'prompt_tokens' => $response['prompt_tokens'],
                    'completion_tokens' => $response['completion_tokens'],
                ],
            );

            $this->registry->logExecution($organization, $this, $result, step: 'program_setup');

            return $result;
        } catch (RuntimeException $e) {
            Log::warning('onboarding_agent.failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);

            return $this->applyFallbackSetup($organization, $rubric, $e->getMessage());
        }
    }

    /**
     * @param  mixed  $criteria
     * @return list<array<string, mixed>>
     */
    private function normalizeCriteria(mixed $criteria): array
    {
        if (! is_array($criteria) || $criteria === []) {
            return Rubric::defaultCriteria();
        }

        $normalized = [];
        foreach ($criteria as $item) {
            if (! is_array($item) || empty($item['name'])) {
                continue;
            }
            $normalized[] = [
                'name' => (string) $item['name'],
                'weight' => (int) ($item['weight'] ?? 25),
                'description' => (string) ($item['description'] ?? ''),
                'scoring_guide' => (string) ($item['scoring_guide'] ?? ''),
            ];
        }

        return $normalized !== [] ? $normalized : Rubric::defaultCriteria();
    }

    private function applyFallbackSetup(Organization $organization, Rubric $rubric, string $error): AgentResult
    {
        if (! $organization->programs()->exists()) {
            Program::create([
                'organization_id' => $organization->id,
                'name' => "{$organization->name} Cohort",
                'slug' => Str::slug($organization->name).'-cohort-'.Str::lower(Str::random(3)),
                'description' => 'Default cohort program — customize rubric in settings.',
                'opens_at' => now(),
                'closes_at' => now()->addMonths(3),
                'max_applications' => 100,
                'status' => 'open',
                'rubric_id' => $rubric->id,
            ]);
        }

        $result = new AgentResult(
            decision: 'suggest_rubric',
            actionTaken: 'Applied default program template (Gemini unavailable)',
            autonomyLevel: 1,
            status: 'completed',
            metadata: ['error' => $error],
        );

        $this->registry->logExecution($organization, $this, $result, step: 'welcome_sequence');

        return $result;
    }
}
