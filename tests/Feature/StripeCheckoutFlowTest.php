<?php

namespace Tests\Feature;

use App\Models\BusinessAgent;
use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\User;
use App\Services\BillingService;
use App\Services\StripePriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_requires_auth(): void
    {
        $this->get('/billing')->assertRedirect('/login');
    }

    public function test_billing_page_shows_plans_for_demo_user(): void
    {
        $user = $this->demoUser();

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing/Index')
                ->has('organization')
                ->has('plans')
                ->has('stripe')
            );
    }

    public function test_checkout_redirects_back_when_stripe_price_missing(): void
    {
        $this->mock(StripePriceResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')->with('cohort')->andReturn(null);
        });

        $user = $this->demoUser();

        $this->actingAs($user)
            ->post('/billing/checkout/cohort')
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('error');
    }

    public function test_checkout_session_fulfillment_records_arms_length_revenue(): void
    {
        $org = Organization::create([
            'name' => 'Pilot Accelerator',
            'slug' => 'pilot-accelerator',
            'country_code' => 'US',
            'website' => 'https://pilot-accelerator.example',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create(['email' => 'director@pilot-accelerator.example']);
        $org->users()->attach($user->id, ['role' => 'owner']);

        $session = [
            'id' => 'cs_test_123',
            'payment_status' => 'paid',
            'amount_total' => 19900,
            'currency' => 'usd',
            'mode' => 'payment',
            'metadata' => [
                'plan' => 'cohort',
                'revenue_type' => 'arms_length',
                'organization_id' => (string) $org->id,
            ],
        ];

        $charge = app(BillingService::class)->fulfillCheckoutSession($org, $session, $user);

        $this->assertNotNull($charge);
        $this->assertSame('arms_length', $charge->revenue_type);
        $this->assertSame(19900, $charge->amount_cents);
        $this->assertSame('cohort', $charge->plan);

        $org->refresh();
        $this->assertSame('cohort', $org->plan);
        $this->assertSame(55, $org->screenings_quota);

        $this->assertDatabaseHas('revenue_charges', [
            'organization_id' => $org->id,
            'stripe_checkout_session_id' => 'cs_test_123',
            'revenue_type' => 'arms_length',
        ]);
    }

    public function test_fulfillment_is_idempotent_for_same_session(): void
    {
        $org = Organization::create([
            'name' => 'Repeat Org',
            'slug' => 'repeat-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $session = [
            'id' => 'cs_test_duplicate',
            'payment_status' => 'paid',
            'amount_total' => 19900,
            'currency' => 'usd',
            'metadata' => ['plan' => 'cohort', 'revenue_type' => 'arms_length'],
        ];

        $billing = app(BillingService::class);
        $first = $billing->fulfillCheckoutSession($org, $session);
        $second = $billing->fulfillCheckoutSession($org, $session);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, RevenueCharge::where('stripe_checkout_session_id', 'cs_test_duplicate')->count());
    }

    private function demoUser(): User
    {
        $user = User::factory()->create([
            'email' => config('venturelens.demo.email'),
        ]);

        $organization = Organization::firstOrCreate(
            ['slug' => 'demo-incubator'],
            [
                'name' => 'Demo Incubator',
                'country_code' => 'TR',
                'plan' => 'free',
                'screenings_quota' => 50,
                'screenings_used' => 0,
            ]
        );

        $user->organizations()->syncWithoutDetaching([$organization->id => ['role' => 'owner']]);

        BusinessAgent::firstOrCreate(['name' => 'growth'], ['enabled' => true, 'autonomy_level' => 1, 'daily_action_cap' => 5]);
        BusinessAgent::firstOrCreate(['name' => 'support'], ['enabled' => true, 'autonomy_level' => 3, 'daily_action_cap' => 50]);

        return $user;
    }
}
