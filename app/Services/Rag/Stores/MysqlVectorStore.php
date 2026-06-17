<?php

namespace App\Services\Rag\Stores;

use App\Models\KnowledgeChunk;
use App\Services\Rag\Contracts\VectorStoreInterface;
use App\Services\Rag\CosineSimilarity;

class MysqlVectorStore implements VectorStoreInterface
{
    public function upsert(array $documents): void
    {
        foreach ($documents as $doc) {
            KnowledgeChunk::updateOrCreate(
                [
                    'organization_id' => $doc['organization_id'],
                    'chunk_key' => $doc['chunk_key'],
                ],
                [
                    'program_id' => $doc['program_id'],
                    'source_type' => $doc['source_type'],
                    'source_id' => $doc['source_id'],
                    'title' => $doc['title'],
                    'content' => $doc['content'],
                    'content_hash' => $doc['content_hash'],
                    'embedding' => $doc['embedding'],
                    'dimensions' => $doc['dimensions'],
                    'embedding_model' => $doc['embedding_model'],
                    'metadata' => $doc['metadata'],
                    'embedded_at' => now(),
                ]
            );
        }
    }

    public function search(array $queryVector, array $filter, int $limit): array
    {
        $query = KnowledgeChunk::query()
            ->where('organization_id', $filter['organization_id'])
            ->whereNotNull('embedding');

        if (array_key_exists('program_id', $filter) && $filter['program_id'] !== null) {
            $programId = $filter['program_id'];
            $query->where(function ($q) use ($programId) {
                $q->whereNull('program_id')->orWhere('program_id', $programId);
            });
        }

        $searchText = $filter['search_text'] ?? null;
        if (is_string($searchText) && $searchText !== '') {
            $words = $this->keywords($searchText);
            if ($words !== []) {
                $query->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->orWhere('title', 'like', '%'.$word.'%')
                            ->orWhere('content', 'like', '%'.$word.'%');
                    }
                });
            }
        }

        $maxScan = (int) config('rag.retrieval.max_scan_chunks', 100);
        $chunks = $query->orderByDesc('embedded_at')->limit($maxScan)->get();
        $scored = [];

        foreach ($chunks as $chunk) {
            $embedding = $chunk->embedding;
            if (! is_array($embedding) || $embedding === []) {
                continue;
            }

            $score = CosineSimilarity::score($queryVector, array_map('floatval', $embedding));
            $scored[] = [
                'chunk_key' => $chunk->chunk_key,
                'title' => $chunk->title,
                'content' => $chunk->content,
                'score' => $score,
                'metadata' => $chunk->metadata,
                'source_type' => $chunk->source_type,
                'source_id' => $chunk->source_id,
                'program_id' => $chunk->program_id,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    public function deleteMatching(array $filter): void
    {
        $query = KnowledgeChunk::query()->where('organization_id', $filter['organization_id']);

        if (isset($filter['chunk_key'])) {
            $query->where('chunk_key', $filter['chunk_key']);
        }
        if (isset($filter['source_type'])) {
            $query->where('source_type', $filter['source_type']);
        }
        if (isset($filter['source_id'])) {
            $query->where('source_id', $filter['source_id']);
        }

        $query->delete();
    }

    /**
     * @return list<string>
     */
    private function keywords(string $text): array
    {
        $words = preg_split('/\W+/u', strtolower($text)) ?: [];
        $words = array_values(array_filter($words, fn ($w) => strlen($w) >= 3));
        $stop = ['the', 'and', 'for', 'what', 'how', 'does', 'are', 'can', 'you', 'our', 'this', 'that', 'with'];

        return array_slice(array_values(array_diff($words, $stop)), 0, 5);
    }
}
