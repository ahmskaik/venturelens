<?php

namespace Tests\Unit;

use App\Models\AgentExecution;
use App\Models\BusinessAgent;
use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\User;
use App\Services\Agents\FinanceAgent;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_finance_execution_for_stripe_charge(): void
    {
        BusinessAgent::create([
            'name' => 'finance',
            'enabled' => true,
            'autonomy_level' => 3,
            'daily_action_cap' => 100,
        ]);

        $org = Organization::create([
            'name' => 'Pilot',
            'slug' => 'pilot-lab',
            'country_code' => 'US',
            'plan' => 'starter',
            'screenings_quota' => 200,
            'screenings_used' => 0,
        ]);

        $charge = RevenueCharge::create([
            'organization_id' => $org->id,
            'amount_cents' => 29900,
            'currency' => 'usd',
            'plan' => 'starter',
            'revenue_type' => 'arms_length',
            'classification_source' => 'checkout',
            'paid_at' => now(),
        ]);

        $result = app(FinanceAgent::class)->recordStripeCharge($org, $charge);

        $this->assertNotNull($result);
        $this->assertSame('classify_arms_length', $result->decision);
        $this->assertSame(3, $result->autonomyLevel);

        $this->assertDatabaseHas('agent_executions', [
            'organization_id' => $org->id,
            'agent_name' => 'finance',
            'step' => 'stripe_reconcile',
            'decision' => 'classify_arms_length',
        ]);
    }

    public function test_does_not_duplicate_finance_execution_for_same_charge(): void
    {
        $org = Organization::create([
            'name' => 'Pilot',
            'slug' => 'pilot-lab-2',
            'country_code' => 'US',
            'plan' => 'cohort',
            'screenings_quota' => 55,
            'screenings_used' => 0,
        ]);

        $charge = RevenueCharge::create([
            'organization_id' => $org->id,
            'amount_cents' => 19900,
            'currency' => 'usd',
            'plan' => 'cohort',
            'revenue_type' => 'related_party',
            'classification_source' => 'rule',
            'paid_at' => now(),
        ]);

        $agent = app(FinanceAgent::class);
        $this->assertNotNull($agent->recordStripeCharge($org, $charge));
        $this->assertNull($agent->recordStripeCharge($org, $charge));

        $this->assertSame(1, AgentExecution::where('agent_name', 'finance')
            ->where('metadata->revenue_charge_id', $charge->id)
            ->count());
    }

    public function test_checkout_fulfillment_logs_finance_agent_execution(): void
    {
        $org = Organization::create([
            'name' => 'Checkout Org',
            'slug' => 'checkout-org',
            'country_code' => 'US',
            'website' => 'https://checkout.example',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create(['email' => 'buyer@gmail.com']);
        $org->users()->attach($user->id, ['role' => 'owner']);

        app(BillingService::class)->fulfillCheckoutSession($org, [
            'id' => 'cs_finance_test',
            'payment_status' => 'paid',
            'amount_total' => 19900,
            'currency' => 'usd',
            'metadata' => [
                'plan' => 'cohort',
                'revenue_type' => 'arms_length',
            ],
        ], $user);

        $this->assertDatabaseHas('agent_executions', [
            'organization_id' => $org->id,
            'agent_name' => 'finance',
            'step' => 'stripe_reconcile',
            'decision' => 'classify_arms_length',
        ]);
    }
}
