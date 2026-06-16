<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_loads_for_authenticated_user(): void
    {
        [$user] = $this->seedOwner();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Index')
                ->where('profile.name', $user->name)
                ->where('organization.name', 'Demo Incubator')
                ->where('can_manage_organization', true)
                ->where('role', 'owner')
            );
    }

    public function test_user_can_update_personal_profile(): void
    {
        [$user] = $this->seedOwner();

        $this->actingAs($user)
            ->put('/settings/profile', [
                'name' => 'Updated Manager',
                'email' => 'updated@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Manager', $user->name);
        $this->assertSame('updated@example.com', $user->email);
    }

    public function test_user_can_change_password(): void
    {
        [$user] = $this->seedOwner();

        $this->actingAs($user)
            ->put('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_owner_can_update_organization_profile(): void
    {
        [$user, $organization] = $this->seedOwner();

        $this->actingAs($user)
            ->put('/settings/organization', [
                'name' => 'Istanbul Innovation Hub',
                'country_code' => 'tr',
                'website' => 'https://hub.example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $organization->refresh();
        $this->assertSame('Istanbul Innovation Hub', $organization->name);
        $this->assertSame('TR', $organization->country_code);
        $this->assertSame('https://hub.example.com', $organization->website);
    }

    public function test_reviewer_cannot_update_organization_profile(): void
    {
        [$user] = $this->seedOwner();

        $reviewer = User::factory()->create();
        $organization = $user->primaryOrganization();
        $organization->users()->attach($reviewer->id, ['role' => 'reviewer']);

        $this->actingAs($reviewer)
            ->put('/settings/organization', [
                'name' => 'Hacked Name',
                'country_code' => 'US',
                'website' => 'https://evil.test',
            ])
            ->assertForbidden();

        $organization->refresh();
        $this->assertSame('Demo Incubator', $organization->name);
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOwner(): array
    {
        $organization = Organization::create([
            'name' => 'Demo Incubator',
            'slug' => 'demo-incubator',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 50,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => 'owner']);

        return [$user, $organization];
    }
}
