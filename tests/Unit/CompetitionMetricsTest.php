<?php

namespace Tests\Unit;

use App\Models\AgentExecution;
use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\RevenueCharge;
use App\Models\Rubric;
use App\Models\ScreeningResult;
use App\Services\CompetitionMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_compute_founder_hours_and_ai_decision_percent(): void
    {
        $org = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $rubric = Rubric::create([
            'organization_id' => $org->id,
            'name' => 'Test Rubric',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        $program = Program::create([
            'organization_id' => $org->id,
            'slug' => 'test-program',
            'name' => 'Test Program',
            'status' => 'open',
            'rubric_id' => $rubric->id,
        ]);

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => 'Test Startup',
            'founder_name' => 'Founder',
            'founder_email' => 'f@test.com',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        ScreeningResult::create([
            'application_id' => $application->id,
            'model' => 'gemini-2.5-flash',
            'overall_score' => 80,
            'error' => null,
        ]);

        AgentExecution::create([
            'organization_id' => $org->id,
            'agent_name' => 'screening',
            'step' => 'gemini_screen',
            'decision' => 'score_80',
            'action_taken' => 'Screened',
            'autonomy_level' => 3,
            'human_minutes_saved' => 45,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        AgentExecution::create([
            'organization_id' => $org->id,
            'agent_name' => 'growth',
            'step' => 'draft',
            'decision' => 'draft_outreach',
            'action_taken' => 'Drafted',
            'autonomy_level' => 1,
            'human_minutes_saved' => 20,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        RevenueCharge::create([
            'organization_id' => $org->id,
            'amount_cents' => 19900,
            'currency' => 'usd',
            'plan' => 'cohort',
            'revenue_type' => 'arms_length',
            'classification_source' => 'rule',
            'paid_at' => now(),
        ]);

        $metrics = app(CompetitionMetrics::class)->compute();

        $this->assertSame(1, $metrics['activity']['applications_screened']);
        $this->assertSame(0.8, $metrics['impact']['founder_hours_saved']);
        $this->assertSame(50.0, $metrics['ai_operations']['pct_decisions_by_ai']);
        $this->assertSame(199.0, $metrics['business']['arms_length_revenue_usd']);
    }

    public function test_impact_json_route_is_public(): void
    {
        $response = $this->getJson('/api/v1/impact.json');

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'business',
                'activity',
                'ai_operations',
                'impact',
            ]);
    }

    public function test_impact_page_is_public(): void
    {
        $this->get('/impact')->assertOk();
    }
}
