<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentExecution;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ScreeningResult;
use App\Models\UsageRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $organization = $user->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');

        $programIds = $organization->programs()->pluck('id');

        $applicationsToday = \App\Models\Application::whereIn('program_id', $programIds)
            ->whereDate('submitted_at', today())
            ->count();

        $screeningsCompleted = ScreeningResult::whereHas(
            'application',
            fn ($q) => $q->whereIn('program_id', $programIds)
        )->whereNull('error')->count();

        $avgScore = ScreeningResult::whereHas(
            'application',
            fn ($q) => $q->whereIn('program_id', $programIds)
        )->whereNull('error')->avg('overall_score');

        $geminiUsage = UsageRecord::where('organization_id', $organization->id)
            ->where('type', 'screening')
            ->where('recorded_at', '>=', now()->subDays(30))
            ->selectRaw('SUM(gemini_calls) as calls, SUM(tokens) as tokens')
            ->first();

        $recentExecutions = AgentExecution::where('organization_id', $organization->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($e) => [
                'step' => $e->step,
                'agent_name' => $e->agent_name,
                'decision' => $e->decision,
                'action_taken' => $e->action_taken,
                'status' => $e->status,
                'created_at' => $e->created_at?->toIso8601String(),
            ]);

        $programs = $organization->programs()
            ->withCount('applications')
            ->get()
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'status' => $p->status,
                'applications_count' => $p->applications_count,
            ]);

        return Inertia::render('Dashboard/Index', [
            'organization' => [
                'name' => $organization->name,
                'plan' => $organization->plan,
                'screenings_used' => $organization->screenings_used,
                'screenings_quota' => $organization->screenings_quota,
            ],
            'stats' => [
                'applications_today' => $applicationsToday,
                'screenings_completed' => $screeningsCompleted,
                'avg_score' => $avgScore ? round((float) $avgScore, 1) : null,
                'gemini_calls_30d' => (int) ($geminiUsage->calls ?? 0),
                'gemini_tokens_30d' => (int) ($geminiUsage->tokens ?? 0),
            ],
            'programs' => $programs,
            'recent_executions' => $recentExecutions,
        ]);
    }
}
