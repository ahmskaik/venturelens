<?php

namespace App\Services\Integrations;

use App\Jobs\IndexKnowledgeChunksJob;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Services\AgentExecutionLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GohortoProfileImporter
{
    public function __construct(
        private readonly GohortoProfileMapper $mapper,
        private readonly AgentExecutionLogger $agentLogger,
    ) {}

    /**
     * @return array{created: int, skipped: int, dispatched: int, indexed: int, errors: list<string>}
     */
    public function importFromFile(
        string $path,
        Program $program,
        int $limit = 0,
        int $offset = 0,
        bool $skipExisting = true,
        bool $dispatchScreening = false,
        int $dispatchDelaySeconds = 2,
        bool $indexRag = true,
        bool $dryRun = false,
    ): array {
        if (! File::exists($path)) {
            throw new \InvalidArgumentException("File not found: {$path}");
        }

        $payload = json_decode(File::get($path), true);
        if (! is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON export file.');
        }

        $profiles = $payload['profiles'] ?? [];
        if (! is_array($profiles)) {
            throw new \InvalidArgumentException('Export file missing profiles array.');
        }

        if ($offset > 0) {
            $profiles = array_slice($profiles, $offset);
        }

        if ($limit > 0) {
            $profiles = array_slice($profiles, 0, $limit);
        }

        $stats = ['created' => 0, 'skipped' => 0, 'dispatched' => 0, 'indexed' => 0, 'errors' => []];
        $organization = $program->organization;
        $delayIndex = 0;
        $indexDelayIndex = 0;

        foreach ($profiles as $index => $profile) {
            if (! is_array($profile)) {
                $stats['errors'][] = "Row {$index}: invalid profile entry";
                continue;
            }

            $projectId = (int) ($profile['project_id'] ?? 0);
            if ($projectId <= 0) {
                $stats['errors'][] = "Row {$index}: missing project_id";
                continue;
            }

            if ($skipExisting && $this->findExisting($program->id, $projectId)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $mapped = $this->mapper->map($profile);
            } catch (\Throwable $e) {
                $stats['errors'][] = "Project {$projectId}: ".$e->getMessage();
                continue;
            }

            if ($dryRun) {
                $stats['created']++;
                continue;
            }

            $application = DB::transaction(function () use ($program, $mapped, $organization, $projectId) {
                $application = Application::create([
                    'program_id' => $program->id,
                    'startup_name' => $mapped['startup_name'],
                    'founder_name' => $mapped['founder_name'],
                    'founder_email' => $mapped['founder_email'],
                    'country_code' => $mapped['country_code'],
                    'stage' => $mapped['stage'],
                    'sector' => $mapped['sector'],
                    'form_data' => $mapped['form_data'],
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

                $this->agentLogger->log(
                    organization: $organization,
                    step: 'gohorto_import',
                    application: $application,
                    decision: 'import_profile',
                    actionTaken: "Imported Gohorto project #{$projectId} — queueing screening",
                    autonomyLevel: 3,
                    metadata: ['gohorto_project_id' => $projectId],
                );

                return $application;
            });

            $stats['created']++;

            if ($indexRag) {
                $indexJob = IndexKnowledgeChunksJob::dispatch($application->id);
                if ($dispatchDelaySeconds > 0) {
                    $indexJob->delay(now()->addSeconds($dispatchDelaySeconds * $indexDelayIndex));
                }
                $indexDelayIndex++;
                $stats['indexed']++;
            }

            if ($dispatchScreening) {
                $job = ScreenApplicationJob::dispatch($application->id);
                if ($dispatchDelaySeconds > 0) {
                    $job->delay(now()->addSeconds($dispatchDelaySeconds * $delayIndex));
                }
                $delayIndex++;
                $stats['dispatched']++;
            }
        }

        return $stats;
    }

    public function findExisting(int $programId, int $gohortoProjectId): ?Application
    {
        return Application::query()
            ->where('program_id', $programId)
            ->where('form_data->integration->gohorto_project_id', $gohortoProjectId)
            ->first();
    }

    public function ensurePilotProgram(int $quota = 600): Program
    {
        $organization = Organization::firstOrCreate(
            ['slug' => 'gohorto-portfolio-pilot'],
            [
                'name' => 'Gohorto Portfolio Review Pilot',
                'country_code' => 'TR',
                'website' => 'https://gohorto.com',
                'plan' => 'pro',
                'screenings_quota' => $quota,
                'screenings_used' => 0,
            ]
        );

        if ($organization->screenings_quota < $quota) {
            $organization->update(['screenings_quota' => $quota]);
        }

        $demoEmail = config('venturelens.demo.email');
        if ($demoEmail) {
            $demoUser = \App\Models\User::where('email', $demoEmail)->first();
            if ($demoUser && ! $demoUser->organizations()->where('organizations.id', $organization->id)->exists()) {
                $demoUser->organizations()->attach($organization->id, ['role' => 'owner']);
            }
        }

        $rubric = Rubric::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Gohorto Portfolio Evaluation',
            ],
            [
                'criteria' => Rubric::defaultCriteria(),
                'is_default' => true,
            ]
        );

        return Program::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'slug' => 'gohorto-portfolio-review-2026',
            ],
            [
                'name' => 'Gohorto Portfolio Review 2026',
                'description' => 'AI rescreening pilot for existing incubator portfolio startups imported from Gohorto.',
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addYear(),
                'max_applications' => null,
                'status' => 'open',
                'rubric_id' => $rubric->id,
            ]
        );
    }
}
