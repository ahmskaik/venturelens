<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\Program;
use App\Services\AgentExecutionLogger;
use App\Services\FounderApplicationService;
use App\Support\ApplicationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function showApplyForm(Request $request, string $slug): Response
    {
        $program = Program::where('slug', $slug)
            ->where('status', 'open')
            ->with('organization')
            ->firstOrFail();

        $prefill = null;
        $user = $request->user();

        if ($user?->isFounder()) {
            $profile = app(FounderApplicationService::class)->ensureFounderProfile($user);
            $latest = $user->founderApplications()->latest('id')->first();

            $prefill = $latest
                ? ApplicationProfile::unpackToFormPayload($latest)
                : [];

            $prefill['founder_name'] = $user->name;
            $prefill['founder_email'] = $user->email;
            $prefill['country_code'] = $profile->default_country_code ?: ($prefill['country_code'] ?? 'US');
        }

        return Inertia::render('Apply/Form', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'description' => $program->description,
                'organization' => $program->organization->name,
                'accepting' => $program->isAcceptingApplications(),
            ],
            'profile_options' => config('venturelens.project_profile'),
            'prefill' => $prefill,
            'founder_logged_in' => $user?->isFounder() ?? false,
        ]);
    }

    public function store(StoreApplicationRequest $request, string $slug): RedirectResponse
    {
        $program = Program::where('slug', $slug)->with('organization')->firstOrFail();

        abort_unless($program->isAcceptingApplications(), 403, 'This program is not accepting applications.');

        $validated = $request->validated();
        $user = $request->user();

        $application = Application::create([
            'program_id' => $program->id,
            'founder_user_id' => $user?->isFounder() ? $user->id : null,
            'startup_name' => $validated['startup_name'],
            'founder_name' => $validated['founder_name'],
            'founder_email' => $user?->isFounder() ? $user->email : $validated['founder_email'],
            'country_code' => $validated['country_code'],
            'stage' => $validated['stage'],
            'sector' => $validated['sector'] ?? null,
            'form_data' => ApplicationProfile::buildFromValidated($validated),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('pitch_deck')) {
            $this->storeUploadedFile($application, $request->file('pitch_deck'), 'pitch_deck');
        }

        if ($request->hasFile('logo')) {
            $this->storeUploadedFile($application, $request->file('logo'), 'logo');
        }

        app(AgentExecutionLogger::class)->log(
            organization: $program->organization,
            step: 'application_submitted',
            application: $application,
            decision: 'queue_screening',
            actionTaken: 'Application submitted — dispatching ScreenApplicationJob',
        );

        ScreenApplicationJob::dispatch($application->id)->afterResponse();

        return redirect()->route('apply.status', [
            'slug' => $program->slug,
            'token' => $application->status_token,
        ])->with('success', 'Application submitted! Gemini screening has started.')
            ->with('founder_application_id', $user?->isFounder() ? $application->id : null);
    }

    public function status(string $slug, string $token): Response
    {
        $application = Application::where('status_token', $token)
            ->whereHas('program', fn ($q) => $q->where('slug', $slug))
            ->with(['latestScreeningResult', 'program'])
            ->firstOrFail();

        return Inertia::render('Apply/Status', [
            'application' => [
                'startup_name' => $application->startup_name,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'ai_overall_score' => $application->ai_overall_score,
                'founder_portal_url' => $application->founder_user_id
                    ? route('founder.applications.show', $application->id)
                    : (auth()->check() && auth()->user()->isFounder()
                        ? route('founder.applications.show', $application->id)
                        : null),
                'screening' => $application->latestScreeningResult ? [
                    'overall_score' => $application->latestScreeningResult->overall_score,
                    'summary' => $application->latestScreeningResult->summary,
                    'recommendation' => $application->latestScreeningResult->recommendation,
                ] : null,
            ],
            'program' => [
                'name' => $application->program->name,
                'slug' => $application->program->slug,
            ],
        ]);
    }

    private function storeUploadedFile(Application $application, \Illuminate\Http\UploadedFile $file, string $type): void
    {
        $disk = config('venturelens.uploads_disk', 'local');
        $path = $file->store("applications/{$application->id}", $disk);

        ApplicationFile::create([
            'application_id' => $application->id,
            'type' => $type,
            'storage_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
        ]);
    }
}
