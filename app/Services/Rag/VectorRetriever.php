<?php

namespace App\Services\Rag;

use App\Jobs\IndexKnowledgeChunksJob;
use App\Models\KnowledgeChunk;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class VectorRetriever
{
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
        $nameHits = $this->retrieveByName($organization, $programId, $question, min(4, $limit));
        $nameTokens = RagQueryTokenizer::latinWordTokens($question);

        if (! $this->isIndexed($organization->id)) {
            $this->queueIndex($organization, $programId);

            return $nameHits !== []
                ? $this->mergeChunks($nameHits, $this->coldStartChunks($organization, $question, $limit), $limit)
                : $this->coldStartChunks($organization, $question, $limit);
        }

        if ($nameHits !== [] && $this->isStrongNameMatch($nameTokens, $nameHits)) {
            return $this->mergeChunks(
                $this->expandApplicationChunks($organization, $programId, $nameHits, $question),
                [],
                $limit,
            );
        }

        if (! RagQueryTokenizer::looksLikeDataQuery($question)) {
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
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function retrieveByName(Organization $organization, ?int $programId, string $question, int $limit): array
    {
        $phrases = RagQueryTokenizer::latinNamePhrases($question);
        if ($phrases !== []) {
            $byPhrase = $this->retrieveByNamePhrases($organization, $programId, $phrases, $question, $limit);
            if ($byPhrase !== []) {
                return $byPhrase;
            }
        }

        $latinTokens = RagQueryTokenizer::latinWordTokens($question);
        if ($latinTokens !== []) {
            return $this->retrieveByNameTokens($organization, $programId, $latinTokens, $question, $limit);
        }

        return [];
    }

    /**
     * @param  list<string>  $phrases
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function retrieveByNamePhrases(
        Organization $organization,
        ?int $programId,
        array $phrases,
        string $question,
        int $limit,
    ): array {
        $query = KnowledgeChunk::where('organization_id', $organization->id);

        if ($programId) {
            $query->where(function ($q) use ($programId) {
                $q->whereNull('program_id')->orWhere('program_id', $programId);
            });
        }

        $query->where(function ($q) use ($phrases) {
            foreach ($phrases as $phrase) {
                $q->orWhere('title', 'like', '%'.$phrase.'%')
                    ->orWhere('content', 'like', '%'.$phrase.'%');
            }
        });

        return $this->mapChunkResults(
            $query->orderByRaw($this->chunkSectionOrderSql($question))
                ->orderByRaw("CASE WHEN source_type = 'application' THEN 0 ELSE 1 END")
                ->orderByDesc('embedded_at')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function retrieveByNameTokens(Organization $organization, ?int $programId, array $tokens, string $question, int $limit): array
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

        return $this->mapChunkResults(
            $query->orderByRaw($this->chunkSectionOrderSql($question))
                ->orderByRaw("CASE WHEN source_type = 'application' THEN 0 ELSE 1 END")
                ->orderByDesc('embedded_at')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, KnowledgeChunk>  $chunks
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function mapChunkResults($chunks): array
    {
        return $chunks->map(fn (KnowledgeChunk $chunk) => [
            'id' => $chunk->chunk_key,
            'title' => $chunk->title,
            'text' => $chunk->content,
            'meta' => array_merge($chunk->metadata ?? [], [
                'type' => $chunk->source_type,
                'score' => 1.0,
                'name_match' => true,
                'url' => $chunk->metadata['url'] ?? null,
            ]),
        ])->all();
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $hits
     */
    private function isStrongNameMatch(array $tokens, array $hits): bool
    {
        if ($hits === []) {
            return false;
        }

        if ($tokens === []) {
            return true;
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
        $byName = $this->retrieveByName($organization, $programId, $question, $limit);
        if ($byName !== []) {
            return $byName;
        }

        $tokens = RagQueryTokenizer::searchTokens($question);
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

    /**
     * When a startup name matches, pull all indexed chunks for that application
     * (screening, risk flags, overview) instead of only the first SQL hit.
     *
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $nameHits
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function expandApplicationChunks(
        Organization $organization,
        ?int $programId,
        array $nameHits,
        string $question,
    ): array {
        $appIds = [];
        foreach ($nameHits as $hit) {
            if (preg_match('/application:(\d+):/', $hit['id'], $m)) {
                $appIds[] = (int) $m[1];
            }
        }

        $appIds = array_values(array_unique($appIds));
        if ($appIds === []) {
            return $nameHits;
        }

        $query = KnowledgeChunk::where('organization_id', $organization->id)
            ->where('source_type', 'application')
            ->whereIn('source_id', $appIds);

        if ($programId) {
            $query->where(function ($q) use ($programId) {
                $q->whereNull('program_id')->orWhere('program_id', $programId);
            });
        }

        return $query->orderByRaw($this->chunkSectionOrderSql($question))
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
                    'section' => $chunk->metadata['section'] ?? null,
                ]),
            ])
            ->all();
    }

    private function chunkSectionOrderSql(string $question): string
    {
        $q = mb_strtolower($question);

        if (str_contains($q, 'risk') || str_contains($q, 'flag') || str_contains($q, 'مخاطر')) {
            return "CASE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.section'))
                WHEN 'risk_flags' THEN 0
                WHEN 'screening' THEN 1
                WHEN 'overview' THEN 2
                ELSE 3 END";
        }

        if (
            str_contains($q, 'strength') || str_contains($q, 'weakness') || str_contains($q, 'score')
            || str_contains($q, 'screen') || str_contains($q, 'نقاط') || str_contains($q, 'قوة')
            || str_contains($q, 'ضعف') || str_contains($q, 'تقييم')
        ) {
            return "CASE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.section'))
                WHEN 'screening' THEN 0
                WHEN 'risk_flags' THEN 1
                WHEN 'overview' THEN 2
                ELSE 3 END";
        }

        return "CASE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.section'))
            WHEN 'overview' THEN 0
            WHEN 'screening' THEN 1
            WHEN 'risk_flags' THEN 2
            ELSE 3 END";
    }
}
