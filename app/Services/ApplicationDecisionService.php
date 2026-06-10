<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use InvalidArgumentException;

class ApplicationDecisionService
{
    /** @var list<string> */
    public const DECISIONS = ['accept', 'reject', 'shortlist', 'waitlist'];

    public function __construct(
        private readonly AgentExecutionLogger $agentLogger,
    ) {}

    public function record(Application $application, User $user, string $decision): Application
    {
        $decision = strtolower($decision);

        if (! in_array($decision, self::DECISIONS, true)) {
            throw new InvalidArgumentException("Invalid decision: {$decision}");
        }

        $status = match ($decision) {
            'accept' => 'accepted',
            'reject' => 'rejected',
            'shortlist' => 'shortlisted',
            'waitlist' => 'waitlisted',
        };

        $application->loadMissing('program.organization', 'latestScreeningResult');
        $organization = $application->program->organization;
        $screening = $application->latestScreeningResult;

        $application->update([
            'status' => $status,
            'decision_by' => $user->id,
            'decision_at' => now(),
        ]);

        $this->agentLogger->log(
            organization: $organization,
            step: 'committee_decision',
            application: $application,
            agentName: 'screening',
            decision: $decision,
            actionTaken: sprintf(
                '%s recorded %s decision (AI score: %s, recommendation: %s)',
                $user->name,
                $status,
                $application->ai_overall_score ?? 'n/a',
                $screening?->recommendation ?? 'n/a'
            ),
            autonomyLevel: 2,
            confidence: $screening?->overall_score ? min(0.99, (float) $screening->overall_score / 100) : null,
            humanMinutesSaved: 10,
            metadata: [
                'decision_by_user_id' => $user->id,
                'ai_overall_score' => $application->ai_overall_score,
                'ai_recommendation' => $screening?->recommendation,
            ],
        );

        return $application->fresh();
    }
}
