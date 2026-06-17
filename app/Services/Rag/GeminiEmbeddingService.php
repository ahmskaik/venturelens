<?php

namespace App\Services\Rag;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiEmbeddingService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $dimensions,
    ) {}

    public static function fromConfig(): self
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        return new self(
            apiKey: $apiKey,
            model: config('rag.embedding.model', 'gemini-embedding-001'),
            dimensions: (int) config('rag.embedding.dimensions', 768),
        );
    }

    /**
     * @return list<float>
     */
    public function embedQuery(string $text): array
    {
        $ttl = (int) config('rag.retrieval.embed_cache_ttl', 3600);
        $cacheKey = 'rag:embed:'.hash('sha256', $this->model.'|'.mb_strtolower(trim($text)));

        return Cache::remember($cacheKey, $ttl, fn () => $this->embed(
            $text,
            config('rag.embedding.query_task', 'RETRIEVAL_QUERY')
        ));
    }

    /**
     * @return list<float>
     */
    public function embedDocument(string $text, ?string $title = null): array
    {
        return $this->embed($text, config('rag.embedding.document_task', 'RETRIEVAL_DOCUMENT'), $title);
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embedDocuments(array $texts): array
    {
        $vectors = [];
        foreach ($texts as $text) {
            $vectors[] = $this->embedDocument($text);
            usleep(50_000);
        }

        return $vectors;
    }

    /**
     * @return list<float>
     */
    private function embed(string $text, string $taskType, ?string $title = null): array
    {
        $url = sprintf('%s/%s:embedContent?key=%s', self::BASE_URL, $this->model, $this->apiKey);

        $payload = [
            'model' => "models/{$this->model}",
            'content' => [
                'parts' => [['text' => $text]],
            ],
            'taskType' => $taskType,
            'outputDimensionality' => $this->dimensions,
        ];

        if ($title !== null && $taskType === 'RETRIEVAL_DOCUMENT') {
            $payload['title'] = $title;
        }

        $response = Http::timeout(30)->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini embedding failed: '.data_get($response->json(), 'error.message', $response->body())
            );
        }

        $values = data_get($response->json(), 'embedding.values');
        if (! is_array($values) || $values === []) {
            throw new RuntimeException('Gemini embedding returned empty vector.');
        }

        $vector = array_map('floatval', $values);
        $vector = $this->normalize($vector);

        Log::info('gemini.embedding', [
            'model' => $this->model,
            'task' => $taskType,
            'dimensions' => count($vector),
        ]);

        return $vector;
    }

    /** @param list<float> $vector */
    private function normalize(array $vector): array
    {
        $norm = 0.0;
        foreach ($vector as $v) {
            $norm += $v * $v;
        }
        $norm = sqrt($norm);
        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(fn ($v) => $v / $norm, $vector);
    }
}
