<?php

namespace App\Services\Rag\Contracts;

interface VectorStoreInterface
{
    /**
     * @param  list<array{
     *     chunk_key: string,
     *     organization_id: int,
     *     program_id: int|null,
     *     source_type: string,
     *     source_id: int|null,
     *     title: string,
     *     content: string,
     *     content_hash: string,
     *     embedding: list<float>,
     *     dimensions: int,
     *     embedding_model: string,
     *     metadata: array<string, mixed>|null,
     * }>  $documents
     */
    public function upsert(array $documents): void;

    /**
     * @param  list<float>  $queryVector
     * @param  array{organization_id: int, program_id?: int|null, search_text?: string}  $filter
     * @return list<array{
     *     chunk_key: string,
     *     title: string,
     *     content: string,
     *     score: float,
     *     metadata: array<string, mixed>|null,
     *     source_type: string,
     *     source_id: int|null,
     *     program_id: int|null,
     * }>
     */
    public function search(array $queryVector, array $filter, int $limit): array;

    /** @param array{organization_id: int, chunk_key?: string, source_type?: string, source_id?: int} $filter */
    public function deleteMatching(array $filter): void;
}
