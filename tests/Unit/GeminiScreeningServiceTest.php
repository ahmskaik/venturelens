<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\ScreeningResult;
use App\Services\Gemini\GeminiScreeningService;
use App\Services\Gemini\PromptBuilder;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeminiScreeningServiceTest extends TestCase
{
    use RefreshDatabase;
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_screen_application_persists_structured_result(): void
    {
        $organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $rubric = Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'Test Rubric',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        $program = Program::create([
            'organization_id' => $organization->id,
            'name' => 'Test Program',
            'slug' => 'test-program',
            'status' => 'open',
            'rubric_id' => $rubric->id,
        ]);

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => 'Acme',
            'founder_name' => 'Jane',
            'founder_email' => 'jane@acme.test',
            'country_code' => 'US',
            'stage' => 'mvp',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $client = Mockery::mock(\App\Services\Gemini\GeminiClient::class);
        $client->shouldReceive('generateContent')->once()->andReturn([
            'content' => json_encode([
                'overall_score' => 78,
                'criterion_scores' => [
                    ['name' => 'Team', 'score' => 80, 'reasoning' => 'Strong team'],
                ],
                'strengths' => ['Clear vision'],
                'weaknesses' => ['Limited traction'],
                'risk_flags' => [],
                'summary' => 'Promising MVP-stage startup.',
                'recommendation' => 'shortlist',
                'completeness' => 'complete',
                'missing_fields' => [],
            ]),
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'latency_ms' => 900,
            'raw' => ['candidates' => []],
        ]);

        $service = new GeminiScreeningService($client, new PromptBuilder);
        $result = $service->screenApplication($application, $rubric);

        $this->assertInstanceOf(ScreeningResult::class, $result);
        $this->assertSame('78.00', $result->overall_score);
        $this->assertSame('shortlist', $result->recommendation);
        $this->assertNull($result->error);
    }
}
