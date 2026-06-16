<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\FounderProfile;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FounderPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_founder_can_register_and_access_dashboard(): void
    {
        $this->post('/founder/register', [
            'name' => 'Sam Founder',
            'email' => 'sam@startup.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'default_country_code' => 'TR',
        ])->assertRedirect(route('founder.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'sam@startup.test',
            'account_type' => 'founder',
        ]);

        $user = User::where('email', 'sam@startup.test')->first();
        $this->assertNotNull($user->founderProfile);
    }

    public function test_founder_registration_claims_existing_applications_by_email(): void
    {
        [$program] = $this->seedOpenProgram();

        Application::create([
            'program_id' => $program->id,
            'startup_name' => 'Legacy Startup',
            'founder_name' => 'Sam Founder',
            'founder_email' => 'sam@startup.test',
            'country_code' => 'TR',
            'stage' => 'seed',
            'status' => 'screened',
            'submitted_at' => now(),
        ]);

        $this->post('/founder/register', [
            'name' => 'Sam Founder',
            'email' => 'sam@startup.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'default_country_code' => 'TR',
        ]);

        $user = User::where('email', 'sam@startup.test')->first();

        $this->assertSame(1, Application::where('founder_user_id', $user->id)->count());
    }

    public function test_founder_can_view_application_detail(): void
    {
        [$program, $founder, $application] = $this->seedFounderApplication();

        $this->actingAs($founder)
            ->get("/founder/applications/{$application->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Founder/Applications/Show')
                ->where('application.startup_name', 'Nova Ocean')
            );
    }

    public function test_incubator_user_cannot_access_founder_portal(): void
    {
        $incubator = User::factory()->create(['account_type' => 'incubator']);

        $this->actingAs($incubator)
            ->get('/founder/dashboard')
            ->assertForbidden();
    }

    public function test_founder_redirected_from_incubator_dashboard(): void
    {
        $founder = User::factory()->create(['account_type' => 'founder']);
        FounderProfile::create(['user_id' => $founder->id, 'default_country_code' => 'US']);

        $this->actingAs($founder)
            ->get('/dashboard')
            ->assertRedirect(route('founder.dashboard'));
    }

    /**
     * @return array{0: Program, 1: User, 2: Application}
     */
    private function seedFounderApplication(): array
    {
        [$program] = $this->seedOpenProgram();

        $founder = User::factory()->create([
            'email' => 'founder@novaocean.test',
            'account_type' => 'founder',
        ]);

        FounderProfile::create(['user_id' => $founder->id, 'default_country_code' => 'TR']);

        $application = Application::create([
            'program_id' => $program->id,
            'founder_user_id' => $founder->id,
            'startup_name' => 'Nova Ocean',
            'founder_name' => $founder->name,
            'founder_email' => $founder->email,
            'country_code' => 'TR',
            'stage' => 'seed',
            'status' => 'screened',
            'submitted_at' => now(),
        ]);

        return [$program, $founder, $application];
    }

    /**
     * @return array{0: Program}
     */
    private function seedOpenProgram(): array
    {
        $organization = Organization::create([
            'name' => 'Test Org',
            'slug' => 'test-org-founder',
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
            'name' => 'Summer Cohort',
            'slug' => 'summer-founder',
            'status' => 'open',
            'opens_at' => now()->subDay(),
            'rubric_id' => $rubric->id,
        ]);

        return [$program];
    }
}
