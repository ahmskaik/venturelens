<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\Program;
use App\Services\AgentExecutionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function showApplyForm(string $slug): Response
    {
        $program = Program::where('slug', $slug)
            ->where('status', 'open')
            ->with('organization')
            ->firstOrFail();

        return Inertia::render('Apply/Form', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'description' => $program->description,
                'organization' => $program->organization->name,
                'accepting' => $program->isAcceptingApplications(),
            ],
        ]);
    }

    public function store(StoreApplicationRequest $request, string $slug): RedirectResponse
    {
        $program = Program::where('slug', $slug)->with('organization')->firstOrFail();

        abort_unless($program->isAcceptingApplications(), 403, 'This program is not accepting applications.');

        $validated = $request->validated();

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => $validated['startup_name'],
            'founder_name' => $validated['founder_name'],
            'founder_email' => $validated['founder_email'],
            'country_code' => $validated['country_code'],
            'stage' => $validated['stage'],
            'sector' => $validated['sector'] ?? null,
            'form_data' => [
                'one_liner' => $validated['one_liner'] ?? null,
                'problem' => $validated['problem'] ?? null,
                'solution' => $validated['solution'] ?? null,
                'market' => $validated['market'] ?? null,
                'traction' => $validated['traction'] ?? null,
                'team' => $validated['team'] ?? null,
                'funding_needs' => $validated['funding_needs'] ?? null,
            ],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('pitch_deck')) {
            $this->storeUploadedFile($application, $request->file('pitch_deck'), 'pitch_deck');
        }

        app(AgentExecutionLogger::class)->log(
            organization: $program->organization,
            step: 'application_submitted',
            application: $application,
            decision: 'queue_screening',
            actionTaken: 'Application submitted — dispatching ScreenApplicationJob',
        );

        ScreenApplicationJob::dispatch($application->id);

        return redirect()->route('apply.status', [
            'slug' => $program->slug,
            'token' => $application->status_token,
        ])->with('success', 'Application submitted! Gemini screening has started.');
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
