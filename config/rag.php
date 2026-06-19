<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vector store driver
    |--------------------------------------------------------------------------
    | Set RAG_VECTOR_STORE in .env:
    |   mysql  — embeddings in knowledge_chunks (default). Best for <10k chunks:
    |            zero network hops, hybrid keyword + cosine in-process.
    |   qdrant — Qdrant vector DB (local Docker or Qdrant Cloud). Worth it at
    |            large scale (100k+ vectors); adds HTTP latency per query.
    */
    'vector_store' => env('RAG_VECTOR_STORE', 'mysql'),

    'allowed_vector_stores' => ['mysql', 'qdrant'],

    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://localhost:6333'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'venturelens_rag'),
    ],

    'embedding' => [
        'model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'dimensions' => (int) env('RAG_EMBEDDING_DIMENSIONS', 768),
        'query_task' => 'RETRIEVAL_QUERY',
        'document_task' => 'RETRIEVAL_DOCUMENT',
    ],

    'retrieval' => [
        'top_k' => (int) env('RAG_TOP_K', 8),
        'candidate_pool' => (int) env('RAG_CANDIDATE_POOL', 16),
        'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.35),
        'hybrid_keyword_boost' => 0.15,
        'max_scan_chunks' => (int) env('RAG_MAX_SCAN_CHUNKS', 100),
        'platform_fast_path_score' => 2.0,
        'embed_cache_ttl' => (int) env('RAG_EMBED_CACHE_TTL', 3600),
    ],

    'chunking' => [
        // 0 = index every application in a cohort during rag:reindex (no cap).
        'max_applications_per_program' => (int) env('RAG_MAX_APPLICATIONS_PER_PROGRAM', 0),
    ],
];
