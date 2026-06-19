<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ScreeningResult;
use App\Services\Rag\CosineSimilarity;
use App\Services\Rag\KnowledgeChunkBuilder;
use App\Services\Rag\RagQueryTokenizer;
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

    #[Test]
    public function latin_name_phrases_extract_startup_from_arabic_question(): void
    {
        $phrases = RagQueryTokenizer::latinNamePhrases('ما هي نقاط القوة لمشروع Palestinian Takke ؟');

        $this->assertContains('Palestinian Takke', $phrases);
    }

    #[Test]
    public function data_query_detects_arabic_strength_question(): void
    {
        $this->assertTrue(RagQueryTokenizer::looksLikeDataQuery('ما هي نقاط القوة لمشروع Palestinian Takke ؟'));
    }

    #[Test]
    public function application_chunks_include_risk_flags_before_summary_and_dedicated_chunk(): void
    {
        $org = new Organization;
        $org->forceFill(['id' => 1]);
        $program = new Program;
        $program->forceFill(['id' => 1, 'organization_id' => 1, 'name' => 'Demo Cohort']);
        $program->setRelation('organization', $org);

        $application = new Application;
        $application->forceFill([
            'id' => 99,
            'program_id' => 1,
            'startup_name' => 'AgriSense',
            'founder_name' => 'Kerem',
            'founder_email' => 'kerem@agrisense.ag',
            'status' => 'screened',
            'sector' => 'AgriTech',
            'stage' => 'seed',
            'country_code' => 'TR',
            'form_data' => [],
        ]);
        $application->setRelation('program', $program);

        $screening = new ScreeningResult;
        $screening->forceFill([
            'overall_score' => 88,
            'recommendation' => 'shortlist',
            'summary' => str_repeat('Long narrative. ', 80),
            'risk_flags' => [
                ['severity' => 'medium', 'message' => 'Founder experience not detailed'],
                ['severity' => 'low', 'message' => 'Modest monthly revenue'],
            ],
        ]);
        $application->setRelation('latestScreeningResult', $screening);

        $chunks = (new KnowledgeChunkBuilder)->applicationChunks($application);
        $keys = array_column($chunks, 'chunk_key');

        $this->assertContains('application:99:risk-flags', $keys);

        $screeningChunk = collect($chunks)->firstWhere('chunk_key', 'application:99:screening');
        $this->assertNotNull($screeningChunk);
        $this->assertStringContainsString('Risk flags:', $screeningChunk['content']);
        $this->assertStringContainsString('[medium]', $screeningChunk['content']);
        $this->assertTrue(
            mb_strpos($screeningChunk['content'], 'Risk flags:')
            < mb_strpos($screeningChunk['content'], 'Summary:'),
            'Risk flags should appear before the long summary in screening chunk',
        );
    }
}
