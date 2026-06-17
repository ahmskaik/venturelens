<?php

namespace App\Services\Rag\Stores;

use App\Models\KnowledgeChunk;
use App\Services\Rag\Contracts\VectorStoreInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class QdrantVectorStore implements VectorStoreInterface
{
    private const COLLECTION_CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $collection,
        private readonly ?string $apiKey = null,
        private readonly int $dimensions = 768,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            baseUrl: rtrim(config('rag.qdrant.url'), '/'),
            collection: config('rag.qdrant.collection'),
            apiKey: config('rag.qdrant.api_key'),
            dimensions: (int) config('rag.embedding.dimensions', 768),
        );
    }

    public function upsert(array $documents): void
    {
        $this->ensureCollection();

        $points = [];
        foreach ($documents as $doc) {
            $pointId = $this->pointId($doc['organization_id'], $doc['chunk_key']);

            $points[] = [
                'id' => $pointId,
                'vector' => $doc['embedding'],
                'payload' => [
                    'chunk_key' => $doc['chunk_key'],
                    'organization_id' => $doc['organization_id'],
                    'program_id' => $doc['program_id'],
                    'source_type' => $doc['source_type'],
                    'source_id' => $doc['source_id'],
                    'title' => $doc['title'],
                    'content' => $doc['content'],
                    'content_hash' => $doc['content_hash'],
                    'metadata' => $doc['metadata'],
                    'embedding_model' => $doc['embedding_model'],
                ],
            ];

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
                    'embedding' => null,
                    'dimensions' => $doc['dimensions'],
                    'embedding_model' => $doc['embedding_model'],
                    'metadata' => $doc['metadata'],
                    'embedded_at' => now(),
                ]
            );
        }

        $this->request('PUT', "/collections/{$this->collection}/points", [
            'points' => $points,
        ]);
    }

    public function search(array $queryVector, array $filter, int $limit): array
    {
        if (! $this->collectionIsReady()) {
            return [];
        }

        $must = [
            ['key' => 'organization_id', 'match' => ['value' => $filter['organization_id']]],
        ];

        $qdrantFilter = ['must' => $must];

        if (array_key_exists('program_id', $filter) && $filter['program_id'] !== null) {
            $qdrantFilter['should'] = [
                ['is_null' => ['key' => 'program_id']],
                ['key' => 'program_id', 'match' => ['value' => $filter['program_id']]],
            ];
            $qdrantFilter['min_should'] = 1;
        }

        $body = [
            'vector' => $queryVector,
            'limit' => min($limit, (int) config('rag.retrieval.max_scan_chunks', 100)),
            'with_payload' => true,
            'filter' => $qdrantFilter,
        ];

        $response = $this->request('POST', "/collections/{$this->collection}/points/search", $body, timeoutSeconds: 10);
        $results = [];

        foreach ($response['result'] ?? [] as $hit) {
            $payload = $hit['payload'] ?? [];
            $results[] = [
                'chunk_key' => $payload['chunk_key'] ?? '',
                'title' => $payload['title'] ?? '',
                'content' => $payload['content'] ?? '',
                'score' => (float) ($hit['score'] ?? 0),
                'metadata' => $payload['metadata'] ?? null,
                'source_type' => $payload['source_type'] ?? 'unknown',
                'source_id' => $payload['source_id'] ?? null,
                'program_id' => $payload['program_id'] ?? null,
            ];
        }

        return $results;
    }

    public function deleteMatching(array $filter): void
    {
        if (! $this->collectionIsReady()) {
            app(MysqlVectorStore::class)->deleteMatching($filter);

            return;
        }

        $must = [
            ['key' => 'organization_id', 'match' => ['value' => $filter['organization_id']]],
        ];

        if (isset($filter['source_type'])) {
            $must[] = ['key' => 'source_type', 'match' => ['value' => $filter['source_type']]];
        }
        if (isset($filter['source_id'])) {
            $must[] = ['key' => 'source_id', 'match' => ['value' => $filter['source_id']]];
        }
        if (isset($filter['chunk_key'])) {
            $must[] = ['key' => 'chunk_key', 'match' => ['value' => $filter['chunk_key']]];
        }

        $this->request('POST', "/collections/{$this->collection}/points/delete", [
            'filter' => ['must' => $must],
        ]);

        app(MysqlVectorStore::class)->deleteMatching($filter);
    }

    private function ensureCollection(): void
    {
        if ($this->collectionIsReady()) {
            return;
        }

        $check = Http::withHeaders($this->headers())
            ->timeout(10)
            ->get("{$this->baseUrl}/collections/{$this->collection}");

        if (! $check->successful()) {
            $this->request('PUT', "/collections/{$this->collection}", [
                'vectors' => [
                    'size' => $this->dimensions,
                    'distance' => 'Cosine',
                ],
            ]);

            Log::info('qdrant.collection_created', ['collection' => $this->collection]);

            $this->ensurePayloadIndexes();
        }

        $this->markCollectionReady();
    }

    private function collectionIsReady(): bool
    {
        return (bool) Cache::remember($this->collectionCacheKey(), self::COLLECTION_CACHE_TTL_SECONDS, function () {
            $check = Http::withHeaders($this->headers())
                ->timeout(5)
                ->get("{$this->baseUrl}/collections/{$this->collection}");

            return $check->successful();
        });
    }

    private function markCollectionReady(): void
    {
        Cache::put($this->collectionCacheKey(), true, self::COLLECTION_CACHE_TTL_SECONDS);
    }

    private function collectionCacheKey(): string
    {
        return "rag:qdrant:ready:{$this->collection}";
    }

    private function ensurePayloadIndexes(): void
    {
        // Qdrant Cloud requires payload indexes for filtered search (see qdrant.tech/documentation/manage-data/indexing).
        $fields = [
            'organization_id' => 'integer',
            'program_id' => 'integer',
            'source_type' => 'keyword',
            'source_id' => 'integer',
            'chunk_key' => 'keyword',
        ];

        foreach ($fields as $field => $schema) {
            $this->createPayloadIndex($field, $schema);
        }
    }

    private function createPayloadIndex(string $field, string $schema): void
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(15)
            ->put("{$this->baseUrl}/collections/{$this->collection}/index", [
                'field_name' => $field,
                'field_schema' => $schema,
            ]);

        if ($response->successful()) {
            Log::info('qdrant.index_created', ['field' => $field]);

            return;
        }

        $body = $response->body();
        if (str_contains(strtolower($body), 'already exists')) {
            return;
        }

        throw new RuntimeException("Qdrant index create for {$field} failed: ".$body);
    }

    /** @return array<string, mixed> */
    private function request(string $method, string $path, array $body = [], int $timeoutSeconds = 30): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($timeoutSeconds)
            ->send($method, "{$this->baseUrl}{$path}", ['json' => $body]);

        if ($response->failed()) {
            throw new RuntimeException("Qdrant {$method} {$path} failed: ".$response->body());
        }

        return $response->json() ?? [];
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($this->apiKey) {
            $headers['api-key'] = $this->apiKey;
        }

        return $headers;
    }

    private function pointId(int $organizationId, string $chunkKey): int
    {
        $bytes = unpack('J', substr(hash('sha256', "{$organizationId}:{$chunkKey}", true), 0, 8));

        return (int) ($bytes[1] & PHP_INT_MAX);
    }
}
