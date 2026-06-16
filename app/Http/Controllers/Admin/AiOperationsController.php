<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunSupportAgentJob;
use App\Models\AgentExecution;
use App\Models\BusinessAgent;
use App\Models\GrowthOutreachDraft;
use App\Models\SupportRequest;
use App\Services\CompetitionMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiOperationsController extends Controller
{
    public function index(Request $request, CompetitionMetrics $metrics): Response
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404);

        $platform = $metrics->all();
        $aiOps = $platform['ai_operations'];

        $executions = AgentExecution::query()
            ->where(function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                    ->orWhereIn('agent_name', ['growth', 'support', 'onboarding', 'finance', 'success']);
            })
            ->latest('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('AiOperations/Index', [
            'stats' => [
                'total_actions' => $aiOps['total_agent_actions'],
                'ai_decision_percent' => $aiOps['pct_decisions_by_ai'],
                'human_hours_displaced' => $aiOps['human_hours_displaced'],
                'by_agent' => $aiOps['by_agent'],
                'autonomy_distribution' => $aiOps['autonomy_distribution'],
            ],
            'agents' => BusinessAgent::orderBy('name')->get()->map(fn ($a) => [
                'name' => $a->name,
                'enabled' => $a->enabled,
                'autonomy_level' => $a->autonomy_level,
                'daily_action_cap' => $a->daily_action_cap,
            ]),
            'executions' => $executions->map(fn ($e) => [
                'agent_name' => $e->agent_name,
                'step' => $e->step,
                'decision' => $e->decision,
                'action_taken' => $e->action_taken,
                'autonomy_level' => $e->autonomy_level,
                'confidence' => $e->confidence,
                'status' => $e->status,
                'created_at' => $e->created_at?->toIso8601String(),
            ]),
            'growth_drafts' => GrowthOutreachDraft::latest()->limit(5)->get()->map(fn ($d) => [
                'id' => $d->id,
                'target_organization' => $d->target_organization,
                'subject' => $d->subject,
                'status' => $d->status,
                'autonomy_level' => $d->autonomy_level,
                'created_at' => $d->created_at?->toIso8601String(),
            ]),
            'support_requests' => SupportRequest::where('organization_id', $organization->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'subject' => $r->subject,
                    'status' => $r->status,
                    'ai_response' => $r->ai_response,
                    'sources' => $r->sources,
                    'confidence' => $r->confidence,
                    'created_at' => $r->created_at?->toIso8601String(),
                ]),
            'programs' => $organization->programs()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeSupportRequest(Request $request): RedirectResponse
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:5000'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
        ]);

        if (! empty($validated['program_id'])) {
            abort_unless(
                $organization->programs()->where('id', $validated['program_id'])->exists(),
                403
            );
        }

        $ticket = SupportRequest::create([
            'organization_id' => $organization->id,
            'user_id' => $request->user()->id,
            'program_id' => $validated['program_id'] ?? null,
            'subject' => $validated['subject'],
            'question' => $validated['question'],
            'status' => 'open',
        ]);

        RunSupportAgentJob::dispatch($ticket->id);

        return back()->with('success', 'Support request submitted — Support Agent is processing.');
    }
}
