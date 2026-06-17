<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\AgentExecutionLogger;
use App\Services\Agents\AgentResult;
use App\Services\Gemini\GeminiClient;
use App\Services\UsageTracker;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ProjectRagChatService
{
    private const CONTEXT_CHAR_LIMIT = 7000;

    private const CHUNK_CHAR_LIMIT = 1000;

    public function __construct(
        private readonly ProjectKnowledgeRetriever $retriever,
        private readonly GeminiClient $client,
        private readonly UsageTracker $usageTracker,
        private readonly AgentExecutionLogger $executionLogger,
    ) {}

    public function sessionFor(User $user, Organization $organization): ChatSession
    {
        return ChatSession::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            ['title' => 'Ask VentureLens']
        );
    }

    /**
     * @return array{user: ChatMessage, assistant: ChatMessage}
     */
    public function ask(
        ChatSession $session,
        Organization $organization,
        User $user,
        string $question,
        ?int $programId = null,
    ): array {
        $programId = $programId ?: $session->program_id;
        $session->update(['program_id' => $programId]);

        $userMessage = $session->messages()->create([
            'role' => 'user',
            'content' => trim($question),
            'program_id' => $programId,
        ]);

        $result = $this->generateAnswer($organization, $programId, $question);

        $assistantMessage = $session->messages()->create([
            'role' => 'assistant',
            'content' => $result['answer'],
            'program_id' => $programId,
            'sources' => $result['sources'],
            'confidence' => $result['confidence'],
            'prompt_tokens' => $result['prompt_tokens'],
            'completion_tokens' => $result['completion_tokens'],
            'retrieval_ms' => $result['retrieval_ms'],
            'generation_ms' => $result['generation_ms'],
            'latency_ms' => $result['latency_ms'],
        ]);

        try {
            $this->usageTracker->recordChat(
                $organization,
                $result['prompt_tokens'],
                $result['completion_tokens'],
            );
        } catch (Throwable $e) {
            Log::warning('rag_chat.usage_record_failed', ['error' => $e->getMessage()]);
        }

        $agentResult = new AgentResult(
            decision: $result['escalate'] ? 'escalate_to_human' : 'rag_answer',
            actionTaken: $result['escalate']
                ? 'RAG chat answered with low confidence — flagged for review'
                : 'RAG chat answered from project knowledge',
            autonomyLevel: $result['escalate'] ? 1 : 3,
            confidence: $result['confidence'],
            humanMinutesSaved: $result['escalate'] ? 2 : 10,
            metadata: [
                'program_id' => $programId,
                'source_count' => count($result['sources']),
                'chat_message_id' => $assistantMessage->id,
                'latency_ms' => $result['latency_ms'],
            ],
        );

        $this->executionLogger->log(
            organization: $organization,
            step: 'rag_chat_answer',
            agentName: 'support',
            decision: $agentResult->decision,
            actionTaken: $agentResult->actionTaken,
            autonomyLevel: $agentResult->autonomyLevel,
            confidence: $agentResult->confidence,
            humanMinutesSaved: $agentResult->humanMinutesSaved,
            status: $agentResult->status,
            metadata: $agentResult->metadata,
        );

        return ['user' => $userMessage, 'assistant' => $assistantMessage];
    }

    /**
     * @return array{
     *     answer: string,
     *     sources: list<array<string, mixed>>,
     *     confidence: float,
     *     escalate: bool,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     retrieval_ms: int,
     *     generation_ms: int,
     *     latency_ms: int,
     * }
     */
    public function answerForSupportTicket(
        Organization $organization,
        string $question,
        ?int $programId = null,
    ): array {
        return $this->generateAnswer($organization, $programId, $question);
    }

    /**
     * @return array{
     *     answer: string,
     *     sources: list<array<string, mixed>>,
     *     confidence: float,
     *     escalate: bool,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     retrieval_ms: int,
     *     generation_ms: int,
     *     latency_ms: int,
     * }
     */
    private function generateAnswer(Organization $organization, ?int $programId, string $question): array
    {
        $started = microtime(true);

        $retrieveStarted = microtime(true);
        $chunks = $this->truncateChunks($this->retriever->retrieve($organization, $programId, $question));
        $retrievalMs = (int) round((microtime(true) - $retrieveStarted) * 1000);

        $scopeLabel = $programId
            ? 'single cohort'
            : 'all cohorts in this organization';

        $contextBlocks = array_map(
            fn ($c) => "[{$c['id']}] {$c['title']}\n{$c['text']}",
            $chunks
        );

        $sources = array_map(fn ($c) => [
            'id' => $c['id'],
            'title' => $c['title'],
            'type' => $c['meta']['type'] ?? 'unknown',
            'url' => $c['meta']['url'] ?? null,
        ], $chunks);

        $generationMs = 0;
        $promptTokens = 0;
        $completionTokens = 0;

        try {
            $generationStarted = microtime(true);
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are VentureLens Ask — a professional assistant for incubator staff. '
                    .'Answer using ONLY the retrieved context below. Be concise, accurate, and helpful. '
                    .'Format the answer field as Markdown (bold for names, numbered or bullet lists where helpful). '
                    .'For founder or person questions, use founder lines and startup overview in context. '
                    .'If the person is not in context, say you have no record of them in this workspace. '
                    .'Set escalate to true only for legal, contract, or account-deletion requests. '
                    .'Return valid JSON only with keys: answer, confidence, escalate.',
                userPrompt: json_encode([
                    'scope' => $scopeLabel,
                    'organization' => $organization->name,
                    'question' => $question,
                    'retrieved_context' => $contextBlocks,
                ], JSON_UNESCAPED_UNICODE),
                generationConfig: [
                    'maxOutputTokens' => 1024,
                ],
                timeoutSeconds: (int) config('services.gemini.chat_timeout', 30),
                maxRetries: (int) config('services.gemini.chat_max_retries', 2),
            );
            $generationMs = (int) round((microtime(true) - $generationStarted) * 1000);

            $parsed = $this->parseModelJson($response['content']);
            if (! is_array($parsed) || empty($parsed['answer'])) {
                throw new RuntimeException('Invalid RAG response JSON');
            }

            $confidence = (float) ($parsed['confidence'] ?? 0.75);
            $escalate = (bool) ($parsed['escalate'] ?? false);

            if ($confidence >= 0.7 && ! $escalate) {
                $escalate = false;
            }

            $promptTokens = $response['prompt_tokens'];
            $completionTokens = $response['completion_tokens'];

            return [
                'answer' => trim((string) $parsed['answer']),
                'sources' => $sources,
                'confidence' => $confidence,
                'escalate' => $escalate,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'retrieval_ms' => $retrievalMs,
                'generation_ms' => $generationMs,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        } catch (Throwable $e) {
            if (isset($generationStarted)) {
                $generationMs = (int) round((microtime(true) - $generationStarted) * 1000);
            }

            Log::warning('rag_chat.failed', [
                'error' => $e->getMessage(),
                'retrieval_ms' => $retrievalMs,
            ]);

            $errorAnswer = str_contains($e->getMessage(), 'quota') || str_contains($e->getMessage(), '429')
                ? 'Gemini API quota was exceeded. Please wait a minute and try again, or check billing in Google AI Studio.'
                : 'I could not generate an answer right now. Please try again or contact support at noreply@venturelens.app.';

            return [
                'answer' => $errorAnswer,
                'sources' => $sources,
                'confidence' => 0.0,
                'escalate' => true,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'retrieval_ms' => $retrievalMs,
                'generation_ms' => $generationMs,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        }
    }

    /**
     * @param  list<array{id: string, title: string, text: string, meta: array<string, mixed>}>  $chunks
     * @return list<array{id: string, title: string, text: string, meta: array<string, mixed>}>
     */
    private function truncateChunks(array $chunks): array
    {
        $total = 0;
        $trimmed = [];

        foreach ($chunks as $chunk) {
            $text = mb_substr($chunk['text'], 0, self::CHUNK_CHAR_LIMIT);
            $block = $chunk;
            $block['text'] = $text;
            $total += mb_strlen($text) + mb_strlen($chunk['title']);
            $trimmed[] = $block;

            if ($total >= self::CONTEXT_CHAR_LIMIT) {
                break;
            }
        }

        return $trimmed;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseModelJson(string $content): ?array
    {
        $parsed = json_decode($content, true);
        if (is_array($parsed)) {
            return $parsed;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/su', $content, $matches)) {
            $parsed = json_decode($matches[1], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        if (preg_match('/\{.*\}/su', $content, $matches)) {
            $parsed = json_decode($matches[0], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }
}
