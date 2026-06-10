<?php

namespace App\Services;

use App\Models\Application;
use App\Models\FounderCommunication;
use App\Models\User;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class FounderCommunicationService
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly AgentExecutionLogger $agentLogger,
    ) {}

    public function draftForDecision(Application $application, string $decision): FounderCommunication
    {
        $application->loadMissing('program.organization', 'latestScreeningResult');
        $organization = $application->program->organization;
        $screening = $application->latestScreeningResult;

        $decisionKey = match ($decision) {
            'accept', 'accepted' => 'accepted',
            'reject', 'rejected' => 'rejected',
            'shortlist', 'shortlisted' => 'shortlisted',
            'waitlist', 'waitlisted' => 'waitlisted',
            default => $decision,
        };

        try {
            $response = $this->client->generateContent(
                model: config('services.gemini.models.flash', 'gemini-2.5-flash'),
                systemPrompt: 'You are drafting founder feedback emails for an incubator program using VentureLens. Be respectful, specific, and constructive. Return JSON only.',
                userPrompt: json_encode([
                    'decision' => $decisionKey,
                    'startup' => [
                        'name' => $application->startup_name,
                        'founder_name' => $application->founder_name,
                        'program' => $application->program->name,
                        'organization' => $organization->name,
                    ],
                    'screening' => [
                        'overall_score' => $screening?->overall_score,
                        'summary' => $screening?->summary,
                        'strengths' => $screening?->strengths ?? [],
                        'weaknesses' => $screening?->weaknesses ?? [],
                        'recommendation' => $screening?->recommendation,
                    ],
                    'output_schema' => [
                        'subject' => 'string',
                        'body' => 'string (plain text, use paragraphs with \\n\\n)',
                    ],
                ], JSON_PRETTY_PRINT),
            );

            $parsed = json_decode($response['content'], true);
            if (! is_array($parsed)) {
                throw new RuntimeException('Invalid founder email JSON');
            }

            $subject = $parsed['subject'] ?? $this->fallbackSubject($decisionKey, $application->program->name);
            $body = $parsed['body'] ?? $this->fallbackBody($decisionKey, $application);
        } catch (RuntimeException $e) {
            Log::warning('founder_email.draft_failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            $subject = $this->fallbackSubject($decisionKey, $application->program->name);
            $body = $this->fallbackBody($decisionKey, $application);
        }

        return FounderCommunication::create([
            'application_id' => $application->id,
            'organization_id' => $organization->id,
            'decision' => $decisionKey,
            'subject' => $subject,
            'body' => $body,
            'status' => 'draft',
            'metadata' => ['generated_by' => 'gemini'],
        ]);
    }

    public function send(FounderCommunication $communication, User $approver): FounderCommunication
    {
        if ($communication->status !== 'draft') {
            throw new RuntimeException('Only draft communications can be sent.');
        }

        $communication->loadMissing('application.program.organization');
        $application = $communication->application;

        Mail::raw($communication->body, function ($message) use ($application, $communication) {
            $message->to($application->founder_email, $application->founder_name)
                ->subject($communication->subject);
        });

        $communication->update([
            'status' => 'sent',
            'approved_by' => $approver->id,
            'sent_at' => now(),
        ]);

        $this->agentLogger->log(
            organization: $communication->organization,
            step: 'founder_email_sent',
            application: $application,
            agentName: 'screening',
            decision: 'email_sent',
            actionTaken: sprintf(
                '%s approved and sent %s email to %s',
                $approver->name,
                $communication->decision,
                $application->founder_email
            ),
            autonomyLevel: 2,
            humanMinutesSaved: 20,
            metadata: [
                'founder_communication_id' => $communication->id,
                'approved_by_user_id' => $approver->id,
            ],
        );

        return $communication->fresh();
    }

    private function fallbackSubject(string $decision, string $programName): string
    {
        return match ($decision) {
            'accepted' => "Congratulations — accepted to {$programName}",
            'rejected' => "Update on your {$programName} application",
            'shortlisted' => "You're on the shortlist — {$programName}",
            'waitlisted' => "Waitlist update — {$programName}",
            default => "Update on your {$programName} application",
        };
    }

    private function fallbackBody(string $decision, Application $application): string
    {
        $name = $application->founder_name;
        $startup = $application->startup_name;

        return match ($decision) {
            'accepted' => "Hi {$name},\n\nCongratulations! {$startup} has been accepted into {$application->program->name}. We will follow up with next steps shortly.\n\nBest regards,\nThe program team",
            'rejected' => "Hi {$name},\n\nThank you for applying with {$startup}. After careful review we will not be moving forward this cohort. We encourage you to apply again in the future.\n\nBest regards,\nThe program team",
            'shortlisted' => "Hi {$name},\n\nGreat news — {$startup} has been shortlisted for {$application->program->name}. We will be in touch with next steps.\n\nBest regards,\nThe program team",
            'waitlisted' => "Hi {$name},\n\nThank you for your application for {$startup}. You have been placed on our waitlist for {$application->program->name}. We will notify you if a spot opens.\n\nBest regards,\nThe program team",
            default => "Hi {$name},\n\nWe have an update regarding your application for {$startup}.\n\nBest regards,\nThe program team",
        };
    }
}
