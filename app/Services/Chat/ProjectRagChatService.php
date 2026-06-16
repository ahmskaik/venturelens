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

class ProjectRagChatService
{
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
        ]);

        $this->usageTracker->recordChat(
            $organization,
            $result['prompt_tokens'],
            $result['completion_tokens'],
        );

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
     * Used by legacy SupportRequest flow (AI Operations form).
     *
     * @return array{answer: string, sources: list<array<string, mixed>>, confidence: float, escalate: bool, prompt_tokens: int, completion_tokens: int}
     */
    public function answerForSupportTicket(
        Organization $organization,
        string $question,
        ?int $programId = null,
    ): array {
        return $this->generateAnswer($organization, $programId, $question);
    }

    /**
     * @return array{answer: string, sources: list<array<string, mixed>>, confidence: float, escalate: bool, prompt_tokens: int, completion_tokens: int}
     */
    private function generateAnswer(Organization $organization, ?int $programId, string $question): array
    {
        $chunks = $this->retriever->retrieve($organization, $programId, $question);
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

        $recentHistory = '';

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are VentureLens Ask — a professional assistant for incubator staff. '
                    .'Answer using ONLY the retrieved context below. Be concise, accurate, and helpful. '
                    .'If context covers the question, answer confidently. '
                    .'Set escalate to true only when the question requires human judgment not present in context (legal, custom contracts, account deletion). '
                    .'Never invent application data not in context. Return JSON only.',
                userPrompt: json_encode([
                    'scope' => $scopeLabel,
                    'organization' => $organization->name,
                    'question' => $question,
                    'retrieved_context' => $contextBlocks,
                    'output_schema' => [
                        'answer' => 'string (markdown allowed, 2-6 sentences unless listing startups)',
                        'confidence' => 'number 0-1',
                        'escalate' => 'boolean',
                    ],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed) || empty($parsed['answer'])) {
                throw new RuntimeException('Invalid RAG response JSON');
            }

            $confidence = (float) ($parsed['confidence'] ?? 0.75);
            $escalate = (bool) ($parsed['escalate'] ?? false);

            if ($confidence >= 0.7 && ! $escalate) {
                $escalate = false;
            }

            return [
                'answer' => trim((string) $parsed['answer']),
                'sources' => $sources,
                'confidence' => $confidence,
                'escalate' => $escalate,
                'prompt_tokens' => $response['prompt_tokens'],
                'completion_tokens' => $response['completion_tokens'],
            ];
        } catch (RuntimeException $e) {
            Log::warning('rag_chat.failed', ['error' => $e->getMessage()]);

            return [
                'answer' => 'I could not generate an answer right now. Please try again or contact support at noreply@venturelens.app.',
                'sources' => $sources,
                'confidence' => 0.0,
                'escalate' => true,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
            ];
        }
    }
}
