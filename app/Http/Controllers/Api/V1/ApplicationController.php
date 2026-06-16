<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Models\Program;
use App\Services\AgentExecutionLogger;
use App\Support\ApplicationProfile;
use Illuminate\Http\JsonResponse;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request, string $slug): JsonResponse
    {
        $program = Program::where('slug', $slug)->with('organization')->firstOrFail();

        abort_unless($program->isAcceptingApplications(), 403);

        $validated = $request->validated();

        $application = Application::create([
            'program_id' => $program->id,
            'startup_name' => $validated['startup_name'],
            'founder_name' => $validated['founder_name'],
            'founder_email' => $validated['founder_email'],
            'country_code' => $validated['country_code'],
            'stage' => $validated['stage'],
            'sector' => $validated['sector'] ?? null,
            'form_data' => ApplicationProfile::buildFromValidated($validated),
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('pitch_deck')) {
            $file = $request->file('pitch_deck');
            $disk = config('venturelens.uploads_disk', 'local');
            $path = $file->store("applications/{$application->id}", $disk);

            ApplicationFile::create([
                'application_id' => $application->id,
                'type' => 'pitch_deck',
                'storage_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?? 'application/pdf',
                'size_bytes' => $file->getSize(),
            ]);
        }

        app(AgentExecutionLogger::class)->log(
            organization: $program->organization,
            step: 'application_submitted',
            application: $application,
            decision: 'queue_screening',
            actionTaken: 'API submission — dispatching ScreenApplicationJob',
        );

        ScreenApplicationJob::dispatch($application->id)->afterResponse();

        return response()->json([
            'id' => $application->id,
            'status' => 'processing',
            'status_token' => $application->status_token,
            'message' => 'Application received. Gemini screening queued.',
        ], 202);
    }

    public function show(string $id): JsonResponse
    {
        $application = Application::with(['latestScreeningResult', 'program'])
            ->findOrFail($id);

        return response()->json([
            'id' => $application->id,
            'startup_name' => $application->startup_name,
            'status' => $application->status,
            'ai_overall_score' => $application->ai_overall_score,
            'submitted_at' => $application->submitted_at,
            'screening_result' => $application->latestScreeningResult,
        ]);
    }
}
