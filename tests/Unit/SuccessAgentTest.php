<?php

namespace Tests\Unit;

use App\Models\BusinessAgent;
use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\SuccessOutreachDraft;
use App\Models\User;
use App\Services\Agents\SuccessAgent;
use App\Services\BillingService;
use App\Services\Gemini\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SuccessAgentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_records_testimonial_draft_after_checkout(): void
    {
        BusinessAgent::create(['name' => 'success', 'enabled' => true, 'autonomy_level' => 1, 'daily_action_cap' => 30]);
        BusinessAgent::create(['name' => 'finance', 'enabled' => true, 'autonomy_level' => 3, 'daily_action_cap' => 100]);

        $org = Organization::create([
            'name' => 'Buyer Org',
            'slug' => 'buyer-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create(['email' => 'buyer@gmail.com']);
        $org->users()->attach($user->id, ['role' => 'owner']);

        $mock = Mockery::mock(GeminiClient::class);
        $mock->shouldReceive('generateContent')->once()->andReturn([
            'content' => json_encode([
                'subject' => 'Share your VentureLens story?',
                'body' => 'Hi there, would you share a quick testimonial?',
                'confidence' => 0.88,
            ]),
            'prompt_tokens' => 50,
            'completion_tokens' => 40,
            'latency_ms' => 300,
            'raw' => [],
        ]);
        $this->app->instance(GeminiClient::class, $mock);

        app(BillingService::class)->fulfillCheckoutSession($org, [
            'id' => 'cs_success_test',
            'payment_status' => 'paid',
            'amount_total' => 19900,
            'currency' => 'usd',
            'metadata' => ['plan' => 'cohort', 'revenue_type' => 'arms_length'],
        ], $user);

        $this->assertSame(1, SuccessOutreachDraft::where('organization_id', $org->id)->count());
        $this->assertDatabaseHas('agent_executions', [
            'organization_id' => $org->id,
            'agent_name' => 'success',
            'step' => 'testimonial_request_drafted',
        ]);
    }
}
