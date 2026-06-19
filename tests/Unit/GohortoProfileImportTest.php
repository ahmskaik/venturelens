<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Services\Integrations\GohortoProfileImporter;
use App\Services\Integrations\GohortoProfileMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GohortoProfileImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapper_maps_gohorto_profile_to_application_fields(): void
    {
        $profile = [
            'project_id' => 3365,
            'project_name' => 'Angela Manning',
            'context' => [
                'basic_info' => [
                    'name' => 'Angela Manning',
                    'short_description' => 'AI logistics for ports',
                    'stage' => 'MVP Stage',
                    'business_types' => ['B2B'],
                    'Current business status' => 'Not Active',
                    'Are you registered or incorporated?' => 'No',
                    'founding_year' => '2020',
                    'team_size' => 5,
                    'founders_count' => '2',
                    'target_customers' => 'Port operators',
                    'Is the project revenue generating?' => 'No',
                    'Has received investments?' => 'No',
                ],
                'pitch_media' => ['website' => 'https://example.com', 'youtube_link' => null],
                'funding' => ['required_fund' => '100000', 'use_of_funds' => 'Hiring'],
                'categories' => ['Agro Tech'],
                'document_extracts' => [],
                'team' => [
                    'owners' => [
                        ['name' => 'Jane Founder', 'nationality' => 'Turkey', 'residency' => 'Turkey', 'bio' => 'CEO'],
                    ],
                    'members' => [],
                ],
            ],
        ];

        $mapped = (new GohortoProfileMapper)->map($profile);

        $this->assertSame('Angela Manning', $mapped['startup_name']);
        $this->assertSame('Jane Founder', $mapped['founder_name']);
        $this->assertSame('gohorto-3365@import.venturelens.local', $mapped['founder_email']);
        $this->assertSame('TR', $mapped['country_code']);
        $this->assertSame('mvp', $mapped['stage']);
        $this->assertSame(3365, $mapped['form_data']['integration']['gohorto_project_id']);
    }

    public function test_importer_creates_application_and_skips_duplicates(): void
    {
        Queue::fake();

        $organization = Organization::create([
            'name' => 'Pilot',
            'slug' => 'pilot-org',
            'country_code' => 'TR',
            'plan' => 'pro',
            'screenings_quota' => 100,
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
            'name' => 'Pilot Program',
            'slug' => 'pilot-program',
            'status' => 'open',
            'rubric_id' => $rubric->id,
        ]);

        $path = $this->writeFixture();

        $importer = app(GohortoProfileImporter::class);

        $first = $importer->importFromFile($path, $program, dispatchScreening: true);
        $second = $importer->importFromFile($path, $program, dispatchScreening: true);

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $first['skipped']);
        $this->assertSame(1, $first['dispatched']);

        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);

        $this->assertDatabaseCount('applications', 1);
        $this->assertSame(3365, Application::first()->form_data['integration']['gohorto_project_id']);
    }

    private function writeFixture(): string
    {
        $dir = storage_path('framework/testing');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.'gohorto-fixture.json';
        file_put_contents($path, json_encode([
            'count' => 1,
            'profiles' => [[
                'project_id' => 3365,
                'project_name' => 'Test Startup',
                'context' => [
                    'basic_info' => [
                        'name' => 'Test Startup',
                        'short_description' => 'Test',
                        'stage' => 'Idea Stage',
                        'business_types' => ['B2B'],
                        'Current business status' => 'Active',
                        'Are you registered or incorporated?' => 'Yes',
                        'founders_count' => '1',
                        'team_size' => 1,
                    ],
                    'pitch_media' => [],
                    'funding' => [],
                    'categories' => ['FinTech'],
                    'document_extracts' => [],
                    'team' => ['owners' => [['name' => 'Founder', 'nationality' => 'Turkey']], 'members' => []],
                ],
            ]],
        ], JSON_THROW_ON_ERROR));

        return $path;
    }
}
