<?php

namespace Tests\Unit;

use App\Jobs\RunOnboardingAgentJob;
use App\Models\BusinessAgent;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\User;
use App\Services\Agents\OnboardingAgent;
use App\Services\Gemini\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OnboardingAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_onboarding_creates_program_and_logs_execution(): void
    {
        BusinessAgent::create([
            'name' => 'onboarding',
            'enabled' => true,
            'autonomy_level' => 2,
            'daily_action_cap' => 50,
        ]);

        $org = Organization::create([
            'name' => 'Pacific Innovation Lab',
            'slug' => 'pacific-lab',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create();
        $org->users()->attach($user->id, ['role' => 'owner']);

        Rubric::create([
            'organization_id' => $org->id,
            'name' => 'Default',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        $mock = Mockery::mock(GeminiClient::class);
        $mock->shouldReceive('generateContent')->once()->andReturn([
            'content' => json_encode([
                'rubric_name' => 'Pacific Startup Rubric',
                'criteria' => Rubric::defaultCriteria(),
                'program' => [
                    'name' => 'Spring 2026 Cohort',
                    'slug' => 'spring-2026',
                    'description' => 'Open applications for early-stage startups.',
                    'max_applications' => 50,
                ],
                'confidence' => 0.9,
            ]),
            'prompt_tokens' => 100,
            'completion_tokens' => 80,
            'latency_ms' => 400,
            'raw' => [],
        ]);
        $this->app->instance(GeminiClient::class, $mock);

        (new RunOnboardingAgentJob($org->id, $user->id))->handle(app(OnboardingAgent::class));

        $this->assertSame(1, Program::where('organization_id', $org->id)->count());
        $this->assertDatabaseHas('agent_executions', [
            'organization_id' => $org->id,
            'agent_name' => 'onboarding',
            'step' => 'program_setup',
        ]);
    }
}
