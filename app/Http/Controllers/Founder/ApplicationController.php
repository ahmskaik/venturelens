<?php

namespace App\Http\Controllers\Founder;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationRequest;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Services\AgentExecutionLogger;
use App\Services\FounderApplicationService;
use App\Support\ApplicationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request, FounderApplicationService $founderService): Response
    {
        $applications = $founderService->applicationsFor($request->user())
            ->map(fn ($app) => $founderService->serializeListItem($app));

        return Inertia::render('Founder/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function show(Request $request, Application $application, FounderApplicationService $founderService): Response
    {
        $founderService->authorizeFounderAccess($request->user(), $application);

        return Inertia::render('Founder/Applications/Show', [
            'application' => $founderService->serializeDetail($application),
        ]);
    }

    public function edit(Request $request, Application $application, FounderApplicationService $founderService): Response
    {
        $founderService->authorizeFounderAccess($request->user(), $application);
        abort_unless($founderService->isEditable($application), 403, 'This application can no longer be edited.');

        $application->load('program.organization');

        return Inertia::render('Founder/Applications/Edit', [
            'application' => [
                'id' => $application->id,
                'status' => $application->status,
                'program' => [
                    'name' => $application->program->name,
                    'slug' => $application->program->slug,
                ],
                'form' => ApplicationProfile::unpackToFormPayload($application),
            ],
            'profile_options' => config('venturelens.project_profile'),
        ]);
    }

    public function update(
        UpdateApplicationRequest $request,
        Application $application,
        FounderApplicationService $founderService,
    ): RedirectResponse {
        $founderService->authorizeFounderAccess($request->user(), $application);
        abort_unless($founderService->isEditable($application), 403);

        $validated = $request->validated();
        $wasNeedsInfo = $application->status === 'needs_info';

        $application->update([
            'startup_name' => $validated['startup_name'],
            'founder_name' => $validated['founder_name'],
            'country_code' => $validated['country_code'],
            'stage' => $validated['stage'],
            'sector' => $validated['sector'] ?? null,
            'form_data' => ApplicationProfile::buildFromValidated($validated),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('pitch_deck')) {
            $this->storeFile($application, $request->file('pitch_deck'), 'pitch_deck');
        }

        if ($request->hasFile('logo')) {
            $this->storeFile($application, $request->file('logo'), 'logo');
        }

        if ($wasNeedsInfo) {
            app(AgentExecutionLogger::class)->log(
                organization: $application->program->organization,
                step: 'founder_profile_updated',
                application: $application,
                decision: 'resubmit',
                actionTaken: 'Founder updated application after needs_info — re-queuing screening',
            );

            ScreenApplicationJob::dispatch($application->id)->afterResponse();
        }

        return redirect()
            ->route('founder.applications.show', $application)
            ->with('success', $wasNeedsInfo
                ? 'Application updated. Gemini is re-screening your submission.'
                : 'Project profile saved.');
    }

    private function storeFile(Application $application, \Illuminate\Http\UploadedFile $file, string $type): void
    {
        $disk = config('venturelens.uploads_disk', 'local');
        $path = $file->store("applications/{$application->id}", $disk);

        if ($path === false) {
            throw new \RuntimeException("Failed to store {$type} on disk [{$disk}].");
        }

        $application->files()->where('type', $type)->delete();

        $application->files()->create([
            'type' => $type,
            'storage_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
        ]);
    }
}
