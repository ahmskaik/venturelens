<?php

namespace App\Console\Commands;

use App\Jobs\IndexKnowledgeChunksJob;
use App\Models\Organization;
use App\Models\Program;
use App\Services\Rag\KnowledgeIndexer;
use Illuminate\Console\Command;

class RagReindexCommand extends Command
{
    protected $signature = 'rag:reindex
                            {--organization= : Organization ID to reindex}
                            {--demo : Reindex Demo Incubator (demo-incubator)}
                            {--program= : Limit to a program/cohort slug or ID}
                            {--sync : Run synchronously instead of queueing}';

    protected $description = 'Build and embed knowledge chunks for RAG (Gemini embeddings + vector store)';

    public function handle(KnowledgeIndexer $indexer): int
    {
        $orgId = $this->option('organization');
        $programId = null;

        if ($this->option('demo')) {
            $demoOrg = Organization::where('slug', 'demo-incubator')->first();
            if (! $demoOrg) {
                $this->error('Demo Incubator not found. Run: php artisan db:seed');

                return self::FAILURE;
            }
            $orgId = (string) $demoOrg->id;
        }

        if ($programSlugOrId = $this->option('program')) {
            $program = is_numeric($programSlugOrId)
                ? Program::find((int) $programSlugOrId)
                : Program::where('slug', $programSlugOrId)->first();
            if (! $program) {
                $this->error("Program {$programSlugOrId} not found.");

                return self::FAILURE;
            }
            $programId = $program->id;
            $orgId ??= (string) $program->organization_id;
        }

        if (! $orgId) {
            $this->error('Pass --organization=<id> or --demo (optional --program=summer-2026).');

            return self::FAILURE;
        }

        $organization = Organization::find($orgId);
        if (! $organization) {
            $this->error("Organization {$orgId} not found.");

            return self::FAILURE;
        }

        $store = config('rag.vector_store', 'mysql');
        $this->line("Vector store: <info>{$store}</info>");

        if ($this->option('sync')) {
            $count = $indexer->reindexOrganization($organization, $programId);
            $this->info("Indexed {$count} chunk(s) for organization {$organization->name}.");

            return self::SUCCESS;
        }

        if ($programId) {
            $queued = $indexer->queueUnindexedApplications(
                Program::findOrFail($programId),
                delaySeconds: 2,
            );
            if ($queued > 0) {
                $this->info("Queued RAG indexing for {$queued} unindexed application(s).");

                return self::SUCCESS;
            }

            $this->info('All applications in this cohort are already indexed.');

            return self::SUCCESS;
        }

        IndexKnowledgeChunksJob::dispatch(
            organizationId: (int) $orgId,
            programId: null,
        );

        $this->info('RAG reindex job dispatched.');

        return self::SUCCESS;
    }
}
