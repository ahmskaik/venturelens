<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_plan_sets_quota_for_starter(): void
    {
        $org = Organization::create([
            'name' => 'Test',
            'slug' => 'test-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 2,
        ]);

        app(BillingService::class)->applyPlan($org, 'starter');

        $org->refresh();
        $this->assertSame('starter', $org->plan);
        $this->assertSame(200, $org->screenings_quota);
    }

    public function test_cohort_package_adds_quota(): void
    {
        $org = Organization::create([
            'name' => 'Test',
            'slug' => 'test-org-2',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        app(BillingService::class)->applyPlan($org, 'cohort', additive: true);

        $org->refresh();
        $this->assertSame('cohort', $org->plan);
        $this->assertSame(55, $org->screenings_quota);
    }
}
