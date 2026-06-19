<?php

namespace App\Services\Rag;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Services\Rag\Contracts\VectorStoreInterface;
use Illuminate\Support\Facades\Log;

class KnowledgeIndexer
{
    public function __construct(
        private readonly KnowledgeChunkBuilder $builder,
        private readonly GeminiEmbeddingService $embeddings,
        private readonly VectorStoreInterface $vectorStore,
    ) {}

    public function indexOrganization(Organization $organization): int
    {
        $docs = $this->builder->platformChunks($organization);

        return $this->indexDocuments($docs);
    }

    public function indexProgram(Program $program): int
    {
        $program->loadMissing('organization');
        $count = $this->indexDocuments($this->builder->programChunk($program));

        $maxApps = (int) config('rag.chunking.max_applications_per_program', 0);
        $query = Application::query()
            ->where('program_id', $program->id)
            ->with(['program.organization', 'latestScreeningResult'])
            ->orderByDesc('submitted_at');

        if ($maxApps > 0) {
            $query->limit($maxApps);
        }

        $query->chunkById(100, function ($applications) use (&$count) {
            foreach ($applications as $application) {
                $count += $this->indexApplication($application);
            }
        });

        return $count;
    }

    public function indexApplication(Application $application): int
    {
        $application->loadMissing(['program.organization', 'latestScreeningResult']);
        if (! $application->program?->organization) {
            return 0;
        }

        $this->vectorStore->deleteMatching([
            'organization_id' => $application->program->organization_id,
            'source_type' => 'application',
            'source_id' => $application->id,
        ]);

        return $this->indexDocuments($this->builder->applicationChunks($application));
    }

    /** Queue embedding jobs for cohort applications not yet in the vector store. */
    public function queueUnindexedApplications(Program $program, int $delaySeconds = 2): int
    {
        $program->loadMissing('organization');
        $orgId = $program->organization_id;

        $indexedIds = \App\Models\KnowledgeChunk::query()
            ->where('organization_id', $orgId)
            ->where('source_type', 'application')
            ->whereNotNull('embedded_at')
            ->distinct()
            ->pluck('source_id');

        $pending = Application::query()
            ->where('program_id', $program->id)
            ->when($indexedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $indexedIds))
            ->orderBy('id')
            ->pluck('id');

        foreach ($pending as $i => $applicationId) {
            $job = \App\Jobs\IndexKnowledgeChunksJob::dispatch($applicationId);
            if ($delaySeconds > 0 && $i > 0) {
                $job->delay(now()->addSeconds($delaySeconds * $i));
            }
        }

        return $pending->count();
    }

    public function reindexOrganization(Organization $organization, ?int $programId = null): int
    {
        $count = $this->indexOrganization($organization);

        $programs = $programId
            ? $organization->programs()->where('id', $programId)->get()
            : $organization->programs;

        foreach ($programs as $program) {
            $count += $this->indexProgram($program);
        }

        Log::info('rag.reindex_complete', [
            'organization_id' => $organization->id,
            'program_id' => $programId,
            'chunks' => $count,
        ]);

        return $count;
    }

    /**
     * @param  list<array{
     *     chunk_key: string,
     *     organization_id: int,
     *     program_id: int|null,
     *     source_type: string,
     *     source_id: int|null,
     *     title: string,
     *     content: string,
     *     metadata: array<string, mixed>,
     * }>  $docs
     */
    private function indexDocuments(array $docs): int
    {
        if ($docs === []) {
            return 0;
        }

        $model = config('rag.embedding.model');
        $dimensions = (int) config('rag.embedding.dimensions', 768);
        $payload = [];

        foreach ($docs as $doc) {
            $hash = hash('sha256', $doc['content']);
            $text = $doc['title']."\n\n".$doc['content'];

            try {
                $vector = $this->embeddings->embedDocument($text, $doc['title']);
            } catch (\Throwable $e) {
                Log::warning('rag.embed_failed', ['chunk' => $doc['chunk_key'], 'error' => $e->getMessage()]);

                continue;
            }

            $payload[] = [
                'chunk_key' => $doc['chunk_key'],
                'organization_id' => $doc['organization_id'],
                'program_id' => $doc['program_id'],
                'source_type' => $doc['source_type'],
                'source_id' => $doc['source_id'],
                'title' => $doc['title'],
                'content' => $doc['content'],
                'content_hash' => $hash,
                'embedding' => $vector,
                'dimensions' => $dimensions,
                'embedding_model' => $model,
                'metadata' => $doc['metadata'],
            ];
        }

        if ($payload !== []) {
            $this->vectorStore->upsert($payload);
        }

        return count($payload);
    }
}
