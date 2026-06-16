<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use App\Models\FounderCommunication;
use App\Models\Program;
use App\Services\ApplicationDecisionService;
use App\Services\FounderCommunicationService;
use App\Support\ApplicationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function organizationIndex(Request $request): Response
    {
        $organization = $request->user()->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');

        $programIds = $organization->programs()->pluck('id');

        $applications = Application::query()
            ->whereIn('program_id', $programIds)
            ->with(['program', 'latestScreeningResult'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (Application $app) => [
                'id' => $app->id,
                'startup_name' => $app->startup_name,
                'founder_name' => $app->founder_name,
                'status' => $app->status,
                'ai_overall_score' => $app->ai_overall_score,
                'submitted_at' => $app->submitted_at?->toIso8601String(),
                'recommendation' => $app->latestScreeningResult?->recommendation,
                'program' => [
                    'id' => $app->program->id,
                    'name' => $app->program->name,
                ],
            ]);

        return Inertia::render('Applications/OrganizationIndex', [
            'applications' => $applications,
        ]);
    }

    public function index(Request $request, Program $program): Response
    {
        $this->authorizeProgramAccess($request, $program);

        $applications = $program->applications()
            ->with('latestScreeningResult')
            ->latest('submitted_at')
            ->get()
            ->map(fn (Application $app) => [
                'id' => $app->id,
                'startup_name' => $app->startup_name,
                'founder_name' => $app->founder_name,
                'status' => $app->status,
                'ai_overall_score' => $app->ai_overall_score,
                'submitted_at' => $app->submitted_at?->toIso8601String(),
                'recommendation' => $app->latestScreeningResult?->recommendation,
            ]);

        return Inertia::render('Applications/Index', [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
            ],
            'applications' => $applications,
        ]);
    }

    public function show(Request $request, Application $application): Response
    {
        $application->load(['program.organization', 'files', 'latestScreeningResult', 'agentExecutions']);

        $this->authorizeProgramAccess($request, $application->program);

        $latestDraft = FounderCommunication::where('application_id', $application->id)
            ->latest('id')
            ->first();

        return Inertia::render('Applications/Show', [
            'application' => [
                'id' => $application->id,
                'startup_name' => $application->startup_name,
                'founder_name' => $application->founder_name,
                'founder_email' => $application->founder_email,
                'country_code' => $application->country_code,
                'stage' => $application->stage,
                'sector' => $application->sector,
                'status' => $application->status,
                'form_data' => $application->form_data,
                'profile_sections' => ApplicationProfile::displaySections($application->form_data),
                'profile_options' => config('venturelens.project_profile'),
                'ai_overall_score' => $application->ai_overall_score,
                'decision_at' => $application->decision_at?->toIso8601String(),
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'files' => $application->files->map(fn ($f) => [
                    'id' => $f->id,
                    'type' => $f->type,
                    'original_filename' => $f->original_filename,
                    'size_bytes' => $f->size_bytes,
                ]),
                'screening' => $application->latestScreeningResult ? [
                    'id' => $application->latestScreeningResult->id,
                    'model' => $application->latestScreeningResult->model,
                    'overall_score' => $application->latestScreeningResult->overall_score,
                    'criterion_scores' => $application->latestScreeningResult->criterion_scores,
                    'strengths' => $application->latestScreeningResult->strengths,
                    'weaknesses' => $application->latestScreeningResult->weaknesses,
                    'risk_flags' => $application->latestScreeningResult->risk_flags,
                    'summary' => $application->latestScreeningResult->summary,
                    'recommendation' => $application->latestScreeningResult->recommendation,
                    'prompt_tokens' => $application->latestScreeningResult->prompt_tokens,
                    'completion_tokens' => $application->latestScreeningResult->completion_tokens,
                    'latency_ms' => $application->latestScreeningResult->latency_ms,
                    'raw_response' => $application->latestScreeningResult->raw_response,
                    'error' => $application->latestScreeningResult->error,
                ] : null,
                'agent_executions' => $application->agentExecutions()
                    ->orderBy('created_at')
                    ->get()
                    ->map(fn ($e) => [
                        'step' => $e->step,
                        'decision' => $e->decision,
                        'action_taken' => $e->action_taken,
                        'status' => $e->status,
                        'created_at' => $e->created_at?->toIso8601String(),
                    ]),
            ],
            'founder_communication' => $latestDraft ? [
                'id' => $latestDraft->id,
                'decision' => $latestDraft->decision,
                'subject' => $latestDraft->subject,
                'body' => $latestDraft->body,
                'status' => $latestDraft->status,
                'sent_at' => $latestDraft->sent_at?->toIso8601String(),
            ] : null,
            'decisions' => ApplicationDecisionService::DECISIONS,
            'program' => [
                'id' => $application->program->id,
                'name' => $application->program->name,
            ],
        ]);
    }

    public function rescreen(Request $request, Application $application): RedirectResponse
    {
        $application->load('program');
        $this->authorizeProgramAccess($request, $application->program);

        ScreenApplicationJob::dispatch($application->id);

        return back()->with('success', 'Gemini screening queued — refresh in a few seconds.');
    }

    public function decision(
        Request $request,
        Application $application,
        ApplicationDecisionService $decisions,
        FounderCommunicationService $communications,
    ): RedirectResponse {
        $application->load('program');
        $this->authorizeProgramAccess($request, $application->program);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(ApplicationDecisionService::DECISIONS)],
        ]);

        $decisions->record($application, $request->user(), $validated['decision']);
        $communications->draftForDecision($application->fresh(), $validated['decision']);

        return back()->with('success', 'Decision recorded — review the AI-drafted founder email below.');
    }

    public function sendCommunication(
        Request $request,
        Application $application,
        FounderCommunication $communication,
        FounderCommunicationService $communications,
    ): RedirectResponse {
        $application->load('program');
        $this->authorizeProgramAccess($request, $application->program);

        abort_unless($communication->application_id === $application->id, 404);

        $communications->send($communication, $request->user());

        return back()->with('success', 'Founder email sent.');
    }

    private function authorizeProgramAccess(Request $request, Program $program): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        $belongs = $user->organizations()
            ->where('organizations.id', $program->organization_id)
            ->exists();

        abort_unless($belongs, 403);
    }
}
