<?php

namespace Tests\Unit;

use App\Services\Rag\CosineSimilarity;
use App\Services\Rag\KnowledgeChunkBuilder;
use App\Models\Organization;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RagServicesTest extends TestCase
{
    #[Test]
    public function cosine_similarity_of_identical_vectors_is_one(): void
    {
        $vector = [0.6, 0.8];

        $this->assertEqualsWithDelta(1.0, CosineSimilarity::score($vector, $vector), 0.0001);
    }

    #[Test]
    public function cosine_similarity_of_orthogonal_vectors_is_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, CosineSimilarity::score([1.0, 0.0], [0.0, 1.0]), 0.0001);
    }

    #[Test]
    public function platform_chunks_include_screening_score_doc(): void
    {
        $org = new Organization;
        $org->forceFill(['id' => 1, 'name' => 'Demo', 'plan' => 'trial', 'screenings_used' => 1, 'screenings_quota' => 5]);
        $builder = new KnowledgeChunkBuilder;
        $chunks = $builder->platformChunks($org);

        $keys = array_column($chunks, 'chunk_key');
        $this->assertContains('platform:screening-score', $keys);
    }
}
