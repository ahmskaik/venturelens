<?php

namespace App\Services\Rag;

use App\Jobs\IndexKnowledgeChunksJob;
use App\Models\KnowledgeChunk;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class VectorRetriever
{
    /** @var list<string> */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'what', 'how', 'does', 'are', 'can', 'you', 'our', 'this', 'that', 'with',
        'do', 'know', 'who', 'tell', 'about', 'have', 'any', 'there', 'from', 'your',
    ];

    public function __construct(
        private readonly GeminiEmbeddingService $embeddings,
        private readonly Contracts\VectorStoreInterface $vectorStore,
        private readonly KnowledgeChunkBuilder $builder,
    ) {}

    /**
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>, score?: float}>
     */
    public function retrieve(Organization $organization, ?int $programId, string $question, int $limit = 8): array
    {
        $limit = $limit > 0 ? $limit : (int) config('rag.retrieval.top_k', 8);
        $tokens = $this->extractSearchTokens($question);
        $nameHits = $tokens !== []
            ? $this->retrieveByNameTokens($organization, $programId, $tokens, min(4, $limit))
            : [];

        if (! $this->isIndexed($organization->id)) {
            $this->queueIndex($organization, $programId);

            return $nameHits !== []
                ? $this->mergeChunks($nameHits, $this->coldStartChunks($organization, $question, $limit), $limit)
                : $this->coldStartChunks($organization, $question, $limit);
        }

        if ($nameHits !== [] && $this->isStrongNameMatch($tokens, $nameHits)) {
            return $this->mergeChunks($nameHits, [], $limit);
        }

        if (! $this->looksLikeDataQuery($question, $tokens)) {
            $platformHits = $this->coldStartChunks($organization, $question, min($limit, 5));
            $topScore = $platformHits[0]['meta']['score'] ?? 0.0;
            if ($topScore >= (float) config('rag.retrieval.platform_fast_path_score', 2.0)) {
                return $platformHits;
            }
        }

        try {
            $queryVector = $this->embeddings->embedQuery($question);
        } catch (\Throwable $e) {
            Log::warning('rag.query_embed_failed', ['error' => $e->getMessage()]);

            return $this->mergeChunks(
                $nameHits,
                $this->fallbackRetrieve($organization, $programId, $question, $limit),
                $limit
            );
        }

        $pool = (int) config('rag.retrieval.candidate_pool', 16);
        $hits = $this->vectorStore->search(
            $queryVector,
            [
                'organization_id' => $organization->id,
                'program_id' => $programId,
                'search_text' => $question,
            ],
            $pool,
        );

        $minSim = (float) config('rag.retrieval.min_similarity', 0.35);
        $keywordBoost = (float) config('rag.retrieval.hybrid_keyword_boost', 0.15);

        $refined = [];
        foreach ($hits as $hit) {
            if ($hit['score'] < $minSim) {
                continue;
            }
            $boost = $this->keywordOverlapScore($question, $hit['title'].' '.$hit['content']);
            $hit['score'] = $hit['score'] + ($boost * $keywordBoost);
            $refined[] = $this->formatHit($hit);
        }

        usort($refined, fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        $refined = array_slice($refined, 0, $limit);

        if ($refined === []) {
            $refined = $this->fallbackRetrieve($organization, $programId, $question, $limit);
        }

        return $this->mergeChunks($nameHits, $refined, $limit);
    }

    private function isIndexed(int $organizationId): bool
    {
        return KnowledgeChunk::where('organization_id', $organizationId)
            ->whereNotNull('embedded_at')
            ->exists();
    }

    private function queueIndex(Organization $organization, ?int $programId): void
    {
        IndexKnowledgeChunksJob::dispatch(
            organizationId: $organization->id,
            programId: $programId,
        );
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $hits
     */
    private function isStrongNameMatch(array $tokens, array $hits): bool
    {
        if ($hits === [] || $tokens === []) {
            return false;
        }

        $haystack = mb_strtolower($hits[0]['title'].' '.$hits[0]['text']);
        foreach ($tokens as $token) {
            if (! str_contains($haystack, mb_strtolower($token))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function retrieveByNameTokens(Organization $organization, ?int $programId, array $tokens, int $limit): array
    {
        $query = KnowledgeChunk::where('organization_id', $organization->id);

        if ($programId) {
            $query->where(function ($q) use ($programId) {
                $q->whereNull('program_id')->orWhere('program_id', $programId);
            });
        }

        foreach ($tokens as $token) {
            $query->where(function ($q) use ($token) {
                $q->where('content', 'like', '%'.$token.'%')
                    ->orWhere('title', 'like', '%'.$token.'%');
            });
        }

        return $query->orderByRaw("CASE WHEN source_type = 'application' THEN 0 ELSE 1 END")
            ->orderByDesc('embedded_at')
            ->limit($limit)
            ->get()
            ->map(fn (KnowledgeChunk $chunk) => [
                'id' => $chunk->chunk_key,
                'title' => $chunk->title,
                'text' => $chunk->content,
                'meta' => array_merge($chunk->metadata ?? [], [
                    'type' => $chunk->source_type,
                    'score' => 1.0,
                    'name_match' => true,
                    'url' => $chunk->metadata['url'] ?? null,
                ]),
            ])
            ->all();
    }

    /**
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $primary
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $secondary
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function mergeChunks(array $primary, array $secondary, int $limit): array
    {
        $seen = [];
        $merged = [];

        foreach ([...$primary, ...$secondary] as $chunk) {
            if (isset($seen[$chunk['id']])) {
                continue;
            }
            $seen[$chunk['id']] = true;
            $merged[] = $chunk;
            if (count($merged) >= $limit) {
                break;
            }
        }

        return $merged;
    }

    /**
     * @return list<string>
     */
    private function extractSearchTokens(string $question): array
    {
        $words = preg_split('/\W+/u', $question) ?: [];
        $tokens = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) < 3) {
                continue;
            }
            if (in_array(mb_strtolower($word), self::STOP_WORDS, true)) {
                continue;
            }
            $tokens[] = $word;
        }

        return array_slice(array_values(array_unique($tokens)), 0, 4);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function looksLikeDataQuery(string $question, array $tokens): bool
    {
        $q = mb_strtolower($question);
        foreach (['startup', 'application', 'founder', 'portfolio', 'applied', 'submitted', 'company', 'pitch', 'know', 'who'] as $needle) {
            if (str_contains($q, $needle)) {
                return true;
            }
        }

        return count($tokens) >= 2;
    }

    /**
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function fallbackRetrieve(Organization $organization, ?int $programId, string $question, int $limit): array
    {
        $fallback = $this->fallbackKeywordRetrieve($organization, $programId, $question, $limit);
        if ($fallback !== []) {
            return $fallback;
        }

        return $this->coldStartChunks($organization, $question, $limit);
    }

    /**
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function coldStartChunks(Organization $organization, string $question, int $limit): array
    {
        $docs = $this->builder->platformChunks($organization);
        $scored = [];

        foreach ($docs as $doc) {
            $score = $this->keywordOverlapScore($question, $doc['title'].' '.$doc['content']);
            $scored[] = ['doc' => $doc, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($row) => [
            'id' => $row['doc']['chunk_key'],
            'title' => $row['doc']['title'],
            'text' => $row['doc']['content'],
            'meta' => array_merge($row['doc']['metadata'], [
                'type' => $row['doc']['source_type'],
                'score' => $row['score'],
                'cold_start' => true,
            ]),
        ], array_slice($scored, 0, $limit));
    }

    /**
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function fallbackKeywordRetrieve(Organization $organization, ?int $programId, string $question, int $limit): array
    {
        $tokens = $this->extractSearchTokens($question);
        if ($tokens !== []) {
            $byName = $this->retrieveByNameTokens($organization, $programId, $tokens, $limit);
            if ($byName !== []) {
                return $byName;
            }
        }

        $query = KnowledgeChunk::where('organization_id', $organization->id);
        if ($programId) {
            $query->where(function ($q) use ($programId) {
                $q->whereNull('program_id')->orWhere('program_id', $programId);
            });
        }

        if ($tokens !== []) {
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $word) {
                    $q->orWhere('title', 'like', '%'.$word.'%')
                        ->orWhere('content', 'like', '%'.$word.'%');
                }
            });
        }

        $chunks = $query->orderByDesc('embedded_at')->limit(80)->get();
        $scored = [];

        foreach ($chunks as $chunk) {
            $score = $this->keywordOverlapScore($question, $chunk->title.' '.$chunk->content);
            if ($score > 0) {
                $scored[] = ['chunk' => $chunk, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        if ($scored === []) {
            return [];
        }

        return array_map(fn ($row) => [
            'id' => $row['chunk']->chunk_key,
            'title' => $row['chunk']->title,
            'text' => $row['chunk']->content,
            'meta' => array_merge($row['chunk']->metadata ?? [], [
                'type' => $row['chunk']->source_type,
                'score' => $row['score'],
                'url' => $row['chunk']->metadata['url'] ?? null,
            ]),
        ], array_slice($scored, 0, $limit));
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return array{id: string, title: string, text: string, meta: array<string, mixed>, score: float}
     */
    private function formatHit(array $hit): array
    {
        return [
            'id' => $hit['chunk_key'],
            'title' => $hit['title'],
            'text' => $hit['content'],
            'meta' => array_merge($hit['metadata'] ?? [], [
                'type' => $hit['source_type'],
                'score' => round($hit['score'], 4),
                'url' => $hit['metadata']['url'] ?? null,
            ]),
            'score' => $hit['score'],
        ];
    }

    private function keywordOverlapScore(string $question, string $haystack): float
    {
        $haystack = mb_strtolower($haystack);
        $words = preg_split('/\W+/u', mb_strtolower($question)) ?: [];
        $score = 0.0;

        foreach ($words as $word) {
            if (mb_strlen($word) < 3) {
                continue;
            }
            if (str_contains($haystack, $word)) {
                $score += 1.0;
            }
        }

        return $score;
    }
}
