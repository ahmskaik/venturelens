<?php

namespace Tests\Feature;

use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\ScreeningResult;
use App\Services\Gemini\GeminiScreeningServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ApplicationScreeningFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_application_submit_dispatches_screening_job(): void
    {
        Queue::fake();

        [$program] = $this->seedProgram();

        $response = $this->postJson("/api/v1/programs/{$program->slug}/applications", [
            'startup_name' => 'NovaPay',
            'founder_name' => 'Sam Founder',
            'founder_email' => 'sam@novapay.test',
            'country_code' => 'TR',
            'stage' => 'mvp',
            'one_liner' => 'Payments for SMEs',
            'problem' => 'SMEs lack access to credit',
            'solution' => 'Embedded lending platform',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('status', 'processing');

        Queue::assertPushed(ScreenApplicationJob::class);
    }

    public function test_screen_job_stores_result_and_updates_application(): void
    {
        [$program, $organization] = $this->seedProgram();

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => 'NovaPay',
            'founder_name' => 'Sam',
            'founder_email' => 'sam@test.com',
            'country_code' => 'TR',
            'stage' => 'mvp',
            'form_data' => ['problem' => 'Test'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $mockResult = ScreeningResult::make([
            'application_id' => $application->id,
            'model' => 'gemini-2.0-flash',
            'overall_score' => 82,
            'criterion_scores' => [],
            'strengths' => ['Strong team'],
            'weaknesses' => [],
            'risk_flags' => [],
            'summary' => 'Good fit.',
            'recommendation' => 'shortlist',
            'raw_response' => ['parsed' => ['completeness' => 'complete']],
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'latency_ms' => 500,
        ]);
        $mockResult->id = 1;

        $mock = Mockery::mock(GeminiScreeningServiceInterface::class);
        $mock->shouldReceive('screenApplication')->once()->andReturnUsing(function () use ($mockResult) {
            $mockResult->save();

            return $mockResult;
        });

        $this->app->instance(GeminiScreeningServiceInterface::class, $mock);

        (new ScreenApplicationJob($application->id))->handle(
            $mock,
            app(\App\Services\DocumentExtractor::class),
            app(\App\Services\AgentExecutionLogger::class),
            app(\App\Services\UsageTracker::class),
        );

        $application->refresh();
        $organization->refresh();

        $this->assertSame('screened', $application->status);
        $this->assertSame('82.00', $application->ai_overall_score);
        $this->assertSame(1, $organization->screenings_used);
    }

    /**
     * @return array{0: Program, 1: Organization}
     */
    private function seedProgram(): array
    {
        $organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org-flow',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 10,
            'screenings_used' => 0,
        ]);

        $rubric = Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'Default',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        $program = Program::create([
            'organization_id' => $organization->id,
            'name' => 'Cohort',
            'slug' => 'cohort-2026',
            'status' => 'open',
            'opens_at' => now()->subDay(),
            'rubric_id' => $rubric->id,
        ]);

        return [$program, $organization];
    }
}
