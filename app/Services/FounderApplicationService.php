<?php

namespace App\Services;

use App\Models\Application;
use App\Models\FounderCommunication;
use App\Models\FounderProfile;
use App\Models\User;
use App\Support\ApplicationProfile;
use Illuminate\Database\Eloquent\Collection;

class FounderApplicationService
{
    /** @var list<string> */
    public const EDITABLE_STATUSES = ['draft', 'submitted', 'needs_info'];

    public function claimApplicationsForUser(User $user): int
    {
        if (! $user->isFounder()) {
            return 0;
        }

        return Application::query()
            ->whereNull('founder_user_id')
            ->where('founder_email', $user->email)
            ->update(['founder_user_id' => $user->id]);
    }

    public function ensureFounderProfile(User $user): FounderProfile
    {
        return FounderProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['default_country_code' => 'US']
        );
    }

    public function authorizeFounderAccess(User $user, Application $application): void
    {
        abort_unless($user->isFounder(), 403);
        abort_unless(
            $application->founder_user_id === $user->id
            || $application->founder_email === $user->email,
            403
        );

        if ($application->founder_user_id === null) {
            $application->update(['founder_user_id' => $user->id]);
        }
    }

    public function isEditable(Application $application): bool
    {
        return in_array($application->status, self::EDITABLE_STATUSES, true);
    }

    /**
     * @return Collection<int, Application>
     */
    public function applicationsFor(User $user): Collection
    {
        return Application::query()
            ->where('founder_user_id', $user->id)
            ->with(['program.organization', 'latestScreeningResult'])
            ->latest('submitted_at')
            ->latest('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeListItem(Application $application): array
    {
        return [
            'id' => $application->id,
            'startup_name' => $application->startup_name,
            'status' => $application->status,
            'ai_overall_score' => $application->ai_overall_score,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'decision_at' => $application->decision_at?->toIso8601String(),
            'editable' => $this->isEditable($application),
            'program' => [
                'id' => $application->program->id,
                'name' => $application->program->name,
                'slug' => $application->program->slug,
            ],
            'organization' => $application->program->organization->name,
            'recommendation' => $application->latestScreeningResult?->recommendation,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDetail(Application $application): array
    {
        $application->loadMissing(['program.organization', 'files', 'latestScreeningResult']);

        $communications = FounderCommunication::query()
            ->where('application_id', $application->id)
            ->where('status', 'sent')
            ->latest('sent_at')
            ->get()
            ->map(fn (FounderCommunication $c) => [
                'id' => $c->id,
                'decision' => $c->decision,
                'subject' => $c->subject,
                'body' => $c->body,
                'sent_at' => $c->sent_at?->toIso8601String(),
            ]);

        $screening = $application->latestScreeningResult;

        return [
            'id' => $application->id,
            'startup_name' => $application->startup_name,
            'founder_name' => $application->founder_name,
            'founder_email' => $application->founder_email,
            'country_code' => $application->country_code,
            'stage' => $application->stage,
            'sector' => $application->sector,
            'status' => $application->status,
            'ai_overall_score' => $application->ai_overall_score,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'decision_at' => $application->decision_at?->toIso8601String(),
            'editable' => $this->isEditable($application),
            'profile_sections' => ApplicationProfile::displaySections($application->form_data),
            'profile_options' => config('venturelens.project_profile'),
            'files' => $application->files->map(fn ($f) => [
                'id' => $f->id,
                'type' => $f->type,
                'original_filename' => $f->original_filename,
            ]),
            'program' => [
                'id' => $application->program->id,
                'name' => $application->program->name,
                'slug' => $application->program->slug,
            ],
            'organization' => $application->program->organization->name,
            'screening' => $screening ? [
                'overall_score' => $screening->overall_score,
                'summary' => $screening->summary,
                'recommendation' => $screening->recommendation,
                'strengths' => $screening->strengths ?? [],
                'weaknesses' => $screening->weaknesses ?? [],
            ] : null,
            'communications' => $communications,
        ];
    }
}
