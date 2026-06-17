<?php

namespace App\Console\Commands;

use App\Jobs\IndexKnowledgeChunksJob;
use App\Models\Organization;
use App\Services\Rag\KnowledgeIndexer;
use Illuminate\Console\Command;

class RagReindexCommand extends Command
{
    protected $signature = 'rag:reindex
                            {--organization= : Organization ID to reindex}
                            {--program= : Limit to a single program/cohort ID}
                            {--sync : Run synchronously instead of queueing}';

    protected $description = 'Build and embed knowledge chunks for RAG (Gemini embeddings + vector store)';

    public function handle(KnowledgeIndexer $indexer): int
    {
        $orgId = $this->option('organization');
        $programId = $this->option('program') ? (int) $this->option('program') : null;

        if (! $orgId) {
            $this->error('Pass --organization=<id> (use sync for all orgs: loop in deploy scripts).');

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

        IndexKnowledgeChunksJob::dispatch(
            organizationId: (int) $orgId,
            programId: $programId,
        );

        $this->info('RAG reindex job dispatched.');

        return self::SUCCESS;
    }
}
