<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Organization;
use App\Services\Rag\KnowledgeIndexer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class IndexKnowledgeChunksJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly ?int $applicationId = null,
        public readonly ?int $organizationId = null,
        public readonly ?int $programId = null,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(KnowledgeIndexer $indexer): void
    {
        if ($this->applicationId) {
            $application = Application::with(['program.organization', 'latestScreeningResult'])
                ->find($this->applicationId);

            if ($application) {
                $indexer->indexApplication($application);
            }

            return;
        }

        if ($this->organizationId) {
            $organization = Organization::find($this->organizationId);
            if ($organization) {
                $indexer->reindexOrganization($organization, $this->programId);
            }

            return;
        }

        Log::warning('rag.index_job_skipped', ['reason' => 'no target specified']);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('rag.index_job_failed', [
            'application_id' => $this->applicationId,
            'organization_id' => $this->organizationId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
