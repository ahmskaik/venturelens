<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CohortManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_update_and_delete_empty_cohort(): void
    {
        [$user, $organization] = $this->seedOrganization('owner');

        $this->actingAs($user)
            ->post('/cohorts', [
                'name' => 'Fall 2026 Cohort',
                'status' => 'open',
                'description' => 'Second intake window.',
                'opens_at' => '2026-09-01',
                'closes_at' => '2026-12-01',
                'max_applications' => 50,
            ])
            ->assertRedirect(route('cohorts.index'));

        $program = Program::where('organization_id', $organization->id)
            ->where('name', 'Fall 2026 Cohort')
            ->first();

        $this->assertNotNull($program);
        $this->assertSame('fall-2026-cohort', $program->slug);
        $this->assertSame('open', $program->status);
        $this->assertSame(50, $program->max_applications);

        $this->actingAs($user)
            ->put("/cohorts/{$program->id}", [
                'name' => 'Fall 2026 — Revised',
                'slug' => 'fall-2026-revised',
                'status' => 'closed',
                'description' => 'Updated copy.',
                'opens_at' => '2026-09-01',
                'closes_at' => '2026-12-01',
                'max_applications' => 75,
            ])
            ->assertRedirect(route('cohorts.index'));

        $program->refresh();
        $this->assertSame('Fall 2026 — Revised', $program->name);
        $this->assertSame('fall-2026-revised', $program->slug);
        $this->assertSame('closed', $program->status);

        $this->actingAs($user)
            ->delete("/cohorts/{$program->id}")
            ->assertRedirect(route('cohorts.index'));

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_cannot_delete_cohort_with_applications(): void
    {
        [$user, $organization] = $this->seedOrganization('owner');

        $program = Program::create([
            'organization_id' => $organization->id,
            'name' => 'Busy Cohort',
            'slug' => 'busy-cohort',
            'status' => 'open',
            'rubric_id' => $organization->rubrics()->first()->id,
        ]);

        Application::create([
            'program_id' => $program->id,
            'startup_name' => 'Acme',
            'founder_name' => 'Alex',
            'founder_email' => 'alex@acme.test',
            'country_code' => 'US',
            'stage' => 'idea',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->from(route('cohorts.index'))
            ->delete("/cohorts/{$program->id}")
            ->assertRedirect(route('cohorts.index'))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_reviewer_cannot_mutate_cohorts(): void
    {
        [$user, $organization] = $this->seedOrganization('reviewer');

        $this->actingAs($user)
            ->post('/cohorts', [
                'name' => 'Blocked Cohort',
                'status' => 'draft',
            ])
            ->assertForbidden();

        $program = Program::create([
            'organization_id' => $organization->id,
            'name' => 'Existing',
            'slug' => 'existing',
            'status' => 'open',
            'rubric_id' => $organization->rubrics()->first()->id,
        ]);

        $this->actingAs($user)
            ->put("/cohorts/{$program->id}", [
                'name' => 'Hacked',
                'slug' => 'hacked',
                'status' => 'open',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete("/cohorts/{$program->id}")
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Organization}
     */
    private function seedOrganization(string $role): array
    {
        $organization = Organization::create([
            'name' => 'Cohort Org',
            'slug' => 'cohort-org',
            'country_code' => 'US',
            'plan' => 'free',
            'screenings_quota' => 10,
            'screenings_used' => 0,
        ]);

        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role]);

        Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'Default',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        return [$user, $organization];
    }
}
