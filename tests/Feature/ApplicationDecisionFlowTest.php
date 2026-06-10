<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\FounderCommunication;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\ScreeningResult;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ApplicationDecisionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_decision_updates_status_and_drafts_founder_email(): void
    {
        Mail::fake();

        [$user, $application] = $this->seedScreenedApplication();

        $mock = Mockery::mock(GeminiClient::class);
        $mock->shouldReceive('generateContent')->once()->andReturn([
            'content' => json_encode([
                'subject' => 'Welcome to the cohort',
                'body' => "Hi {$application->founder_name},\n\nCongratulations!",
            ]),
            'prompt_tokens' => 10,
            'completion_tokens' => 10,
            'latency_ms' => 100,
            'raw' => [],
        ]);
        $this->app->instance(GeminiClient::class, $mock);

        $this->actingAs($user)
            ->post("/applications/{$application->id}/decision", ['decision' => 'accept'])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('accepted', $application->status);
        $this->assertNotNull($application->decision_at);

        $this->assertDatabaseHas('agent_executions', [
            'application_id' => $application->id,
            'step' => 'committee_decision',
            'decision' => 'accept',
        ]);

        $draft = FounderCommunication::where('application_id', $application->id)->first();
        $this->assertNotNull($draft);
        $this->assertSame('draft', $draft->status);
    }

    public function test_send_communication_marks_sent_and_logs_execution(): void
    {
        Mail::fake();

        [$user, $application] = $this->seedScreenedApplication();

        $communication = FounderCommunication::create([
            'application_id' => $application->id,
            'organization_id' => $application->program->organization_id,
            'decision' => 'accepted',
            'subject' => 'Welcome',
            'body' => 'Congratulations on joining the cohort.',
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->post("/applications/{$application->id}/communications/{$communication->id}/send")
            ->assertRedirect();

        $communication->refresh();
        $this->assertSame('sent', $communication->status);
        $this->assertNotNull($communication->sent_at);
        $this->assertSame($user->id, $communication->approved_by);

        $this->assertDatabaseHas('agent_executions', [
            'application_id' => $application->id,
            'step' => 'founder_email_sent',
        ]);
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function seedScreenedApplication(): array
    {
        $organization = Organization::create([
            'name' => 'Decision Org',
            'slug' => 'decision-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 10,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        $rubric = Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'Default',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        $program = Program::create([
            'organization_id' => $organization->id,
            'name' => 'Cohort',
            'slug' => 'cohort',
            'status' => 'open',
            'rubric_id' => $rubric->id,
        ]);

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => 'NovaPay',
            'founder_name' => 'Sam',
            'founder_email' => 'sam@novapay.test',
            'country_code' => 'US',
            'stage' => 'mvp',
            'status' => 'screened',
            'ai_overall_score' => 82,
            'submitted_at' => now(),
        ]);

        ScreeningResult::create([
            'application_id' => $application->id,
            'model' => 'gemini-2.5-flash',
            'overall_score' => 82,
            'summary' => 'Strong team.',
            'recommendation' => 'shortlist',
            'prompt_tokens' => 10,
            'completion_tokens' => 10,
            'latency_ms' => 500,
        ]);

        return [$user, $application];
    }
}
