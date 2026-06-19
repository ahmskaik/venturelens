<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Services\Integrations\GohortoProfileImporter;
use App\Services\Rag\KnowledgeIndexer;
use Illuminate\Console\Command;

class ImportGohortoProfilesCommand extends Command
{
    protected $signature = 'gohorto:import
        {file : Path to Gohorto project-profiles JSON export}
        {--program= : VentureLens program slug (default: gohorto-portfolio-review-2026 with --setup-pilot)}
        {--setup-pilot : Create Gohorto portfolio pilot org + program}
        {--demo : Import into Demo Incubator summer-2026 cohort (visible when logged in as demo@venturelens.app)}
        {--quota=600 : Raise target org screenings_quota to at least this value}
        {--limit=0 : Max profiles to import (0 = all in file)}
        {--offset=0 : Skip first N profiles}
        {--dispatch-screening : Queue ScreenApplicationJob for each import}
        {--delay=2 : Seconds between queued screening jobs}
        {--no-index-rag : Skip RAG indexing for unindexed applications after import}
        {--skip-existing : Skip profiles already imported (default)}
        {--force : Import even if gohorto project_id already exists}
        {--dry-run : Map and count without writing to DB}';

    protected $description = 'Import Gohorto project profile JSON export into VentureLens applications';

    public function handle(GohortoProfileImporter $importer, KnowledgeIndexer $indexer): int
    {
        $file = $this->argument('file');
        if (! is_string($file) || $file === '') {
            $this->error('A JSON export file path is required.');

            return self::FAILURE;
        }

        if (! is_file($file)) {
            $candidate = base_path($file);
            if (is_file($candidate)) {
                $file = $candidate;
            }
        }

        $program = $this->resolveProgram($importer);
        if ($program === null) {
            return self::FAILURE;
        }

        $this->info("Target program: {$program->name} ({$program->slug})");
        $this->info("Organization: {$program->organization->name} ({$program->organization->slug})");
        $this->ensureQuota($program);
        $this->info("Organization quota: {$program->organization->fresh()->screenings_used} / {$program->organization->fresh()->screenings_quota} screenings used");

        if ($program->organization->slug === 'gohorto-portfolio-pilot') {
            $this->warn('These applications are under the Gohorto pilot org — not visible on Demo Incubator. Re-run with --demo or --program=summer-2026.');
        }

        $indexRag = ! $this->option('no-index-rag');

        $stats = $importer->importFromFile(
            path: $file,
            program: $program,
            limit: (int) $this->option('limit'),
            offset: (int) $this->option('offset'),
            skipExisting: ! (bool) $this->option('force'),
            dispatchScreening: (bool) $this->option('dispatch-screening'),
            dispatchDelaySeconds: max(0, (int) $this->option('delay')),
            indexRag: $indexRag,
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Skipped (existing)', $stats['skipped']],
                ['Screening jobs queued', $stats['dispatched']],
                ['RAG index jobs queued (new)', $stats['indexed']],
                ['Errors', count($stats['errors'])],
            ],
        );

        foreach ($stats['errors'] as $error) {
            $this->warn($error);
        }

        if ($this->option('dispatch-screening') && ! $this->option('dry-run')) {
            $this->line('Ensure a queue worker is running: php artisan queue:work');
        }

        if ($indexRag && ! $this->option('dry-run')) {
            $queued = $indexer->queueUnindexedApplications($program, max(1, (int) $this->option('delay')));
            if ($queued > 0) {
                $this->info("Queued RAG indexing for {$queued} unindexed application(s).");
            }
        }

        if (! $this->option('dry-run') && $stats['created'] > 0) {
            $this->line('After screening completes: php artisan impact:snapshot');
        }

        return $stats['errors'] === [] || $stats['created'] > 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function resolveProgram(GohortoProfileImporter $importer): ?Program
    {
        if ($this->option('demo')) {
            $program = Program::where('slug', 'summer-2026')->first();
            if ($program === null) {
                $this->error('Demo program summer-2026 not found. Run: php artisan db:seed');

                return null;
            }
            $this->info('Using Demo Incubator cohort: summer-2026');

            return $program;
        }

        if ($this->option('setup-pilot')) {
            $program = $importer->ensurePilotProgram((int) $this->option('quota'));
            $this->info('Pilot org/program ready: gohorto-portfolio-pilot / gohorto-portfolio-review-2026');

            return $program;
        }

        $slug = $this->option('program');
        if (! is_string($slug) || $slug === '') {
            $this->error('Pass --program=slug or use --setup-pilot to create the default pilot program.');

            return null;
        }

        $program = Program::where('slug', $slug)->first();
        if ($program === null) {
            $this->error("Program not found: {$slug}");

            return null;
        }

        return $program;
    }

    private function ensureQuota(Program $program): void
    {
        $quota = (int) $this->option('quota');
        if ($quota <= 0) {
            return;
        }

        $organization = $program->organization;
        if ($organization->screenings_quota < $quota) {
            $organization->update(['screenings_quota' => $quota]);
            $this->info("Raised screenings_quota to {$quota} for {$organization->slug}");
        }
    }
}
