<?php

namespace App\Providers;

use App\Models\Organization;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiScreeningService;
use App\Services\Gemini\GeminiScreeningServiceInterface;
use App\Services\Rag\Contracts\VectorStoreInterface;
use App\Services\Rag\GeminiEmbeddingService;
use App\Services\Rag\Stores\MysqlVectorStore;
use App\Services\Rag\Stores\QdrantVectorStore;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Smalot\PdfParser\Parser;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiClient::class, fn () => GeminiClient::fromConfig());

        $this->app->bind(GeminiScreeningServiceInterface::class, GeminiScreeningService::class);

        $this->app->singleton(Parser::class, fn () => new Parser);

        $this->app->singleton(GeminiEmbeddingService::class, fn () => GeminiEmbeddingService::fromConfig());

        $this->app->singleton(VectorStoreInterface::class, function () {
            $driver = strtolower((string) config('rag.vector_store', 'mysql'));
            $allowed = config('rag.allowed_vector_stores', ['mysql', 'qdrant']);

            if (! in_array($driver, $allowed, true)) {
                throw new \RuntimeException(
                    "Invalid RAG_VECTOR_STORE \"{$driver}\". Use one of: ".implode(', ', $allowed)
                );
            }

            if ($driver === 'qdrant' && empty(config('rag.qdrant.url'))) {
                throw new \RuntimeException(
                    'RAG_VECTOR_STORE=qdrant requires QDRANT_URL in .env (e.g. https://xxx.cloud.qdrant.io or http://localhost:6333).'
                );
            }

            return match ($driver) {
                'qdrant' => QdrantVectorStore::fromConfig(),
                default => app(MysqlVectorStore::class),
            };
        });
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Organization::class);
        Cashier::$registersRoutes = false;
    }
}
