<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\AgentExecutionLogger;
use App\Services\DocumentExtractor;
use App\Services\Gemini\GeminiScreeningServiceInterface;
use App\Services\UsageTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScreenApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $applicationId,
    ) {}

    public function backoff(): array
    {
        return [5, 15, 45];
    }

    public function handle(
        GeminiScreeningServiceInterface $screeningService,
        DocumentExtractor $documentExtractor,
        AgentExecutionLogger $agentLogger,
        UsageTracker $usageTracker,
    ): void {
        $application = Application::with(['program.organization', 'program.rubric', 'files'])
            ->findOrFail($this->applicationId);

        $organization = $application->program->organization;
        $program = $application->program;

        if (! $organization->hasScreeningQuota()) {
            $application->update(['status' => 'submitted']);
            $agentLogger->log(
                organization: $organization,
                step: 'quota_exceeded',
                application: $application,
                decision: 'block_screening',
                actionTaken: 'Screening blocked — organization quota exceeded',
                status: 'failed',
            );

            return;
        }

        $application->markProcessing();

        $agentLogger->log(
            organization: $organization,
            step: 'intake_received',
            application: $application,
            decision: 'process_application',
            actionTaken: 'Application queued for Gemini screening',
            humanMinutesSaved: 0,
        );

        $documentsSummary = $documentExtractor->summarizeApplicationDocuments($application->id);

        $agentLogger->log(
            organization: $organization,
            step: 'document_parsed',
            application: $application,
            decision: 'documents_ready',
            actionTaken: sprintf('Extracted text from %d file(s)', $application->files->count()),
            metadata: ['chars' => mb_strlen($documentsSummary)],
        );

        $rubric = $program->resolveRubric();

        $agentLogger->log(
            organization: $organization,
            step: 'gemini_screen_called',
            application: $application,
            decision: 'call_gemini',
            actionTaken: 'Invoking Gemini screening API',
            status: 'started',
        );

        $result = $screeningService->screenApplication($application, $rubric, $documentsSummary);

        if ($result->error) {
            $agentLogger->log(
                organization: $organization,
                step: 'gemini_screen_failed',
                application: $application,
                decision: 'screening_error',
                actionTaken: $result->error,
                status: 'failed',
            );

            $application->update(['status' => 'submitted']);

            throw new \RuntimeException($result->error);
        }

        $parsed = $result->raw_response['parsed'] ?? [];
        $completeness = $parsed['completeness'] ?? 'complete';
        $recommendation = $result->recommendation ?? 'needs_review';

        $status = match (true) {
            $completeness === 'incomplete' => 'needs_info',
            default => 'screened',
        };

        $application->update([
            'status' => $status,
            'ai_overall_score' => $result->overall_score,
        ]);

        $organization->incrementScreeningsUsed();

        $usageTracker->recordScreening(
            $organization,
            geminiCalls: 1,
            tokens: $result->prompt_tokens + $result->completion_tokens,
        );

        $agentLogger->log(
            organization: $organization,
            step: 'screening_complete',
            application: $application,
            decision: $recommendation,
            actionTaken: sprintf(
                'Scored %.1f — status set to %s',
                (float) $result->overall_score,
                $status
            ),
            confidence: $this->deriveConfidence($result->overall_score),
            humanMinutesSaved: 45,
            metadata: [
                'overall_score' => $result->overall_score,
                'prompt_tokens' => $result->prompt_tokens,
                'completion_tokens' => $result->completion_tokens,
                'latency_ms' => $result->latency_ms,
                'model' => $result->model,
            ],
        );

        Log::info('screening.complete', [
            'application_id' => $application->id,
            'score' => $result->overall_score,
            'model' => $result->model,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('screening.job_failed', [
            'application_id' => $this->applicationId,
            'error' => $exception?->getMessage(),
        ]);

        Application::whereKey($this->applicationId)->update(['status' => 'submitted']);
    }

    private function deriveConfidence(?float $score): ?float
    {
        if ($score === null) {
            return null;
        }

        return min(0.99, max(0.5, $score / 100));
    }
}
