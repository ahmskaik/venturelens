<?php

namespace App\Services\Chat;

use App\Models\Organization;
use App\Services\Rag\VectorRetriever;

/**
 * @deprecated Use VectorRetriever directly — kept as thin adapter for chat/support services.
 */
class ProjectKnowledgeRetriever
{
    public function __construct(
        private readonly VectorRetriever $retriever,
    ) {}

    /**
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    public function retrieve(Organization $organization, ?int $programId, string $question, int $limit = 8): array
    {
        return $this->retriever->retrieve($organization, $programId, $question, $limit);
    }
}
