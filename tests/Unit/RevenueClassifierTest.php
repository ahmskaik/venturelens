<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\RevenueClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueClassifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_classifies_demo_org_as_related_party(): void
    {
        $org = Organization::create([
            'name' => 'Demo',
            'slug' => 'demo-incubator',
            'country_code' => 'TR',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $type = app(RevenueClassifier::class)->classify($org);

        $this->assertSame('related_party', $type);
    }

    public function test_classifies_new_org_as_arms_length(): void
    {
        $org = Organization::create([
            'name' => 'New Accelerator',
            'slug' => 'new-accelerator-xyz',
            'country_code' => 'US',
            'website' => 'https://newaccelerator.example.com',
            'plan' => 'free',
            'screenings_quota' => 5,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create(['email' => 'director@newaccelerator.example.com']);
        $org->users()->attach($user->id, ['role' => 'owner']);

        $type = app(RevenueClassifier::class)->classify($org, $user);

        $this->assertSame('arms_length', $type);
    }
}
