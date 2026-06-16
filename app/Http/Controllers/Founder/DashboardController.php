<?php

namespace App\Http\Controllers\Founder;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\FounderApplicationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FounderApplicationService $founderService): Response
    {
        $user = $request->user();
        $founderService->claimApplicationsForUser($user);

        $applications = $founderService->applicationsFor($user)
            ->map(fn ($app) => $founderService->serializeListItem($app));

        $openPrograms = Program::query()
            ->where('status', 'open')
            ->with('organization')
            ->withCount('applications')
            ->get()
            ->filter(fn (Program $p) => $p->isAcceptingApplications())
            ->take(5)
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'organization' => $p->organization->name,
                'description' => $p->description,
            ])
            ->values();

        return Inertia::render('Founder/Dashboard/Index', [
            'stats' => [
                'total_applications' => $applications->count(),
                'in_review' => $applications->whereIn('status', ['submitted', 'processing', 'screened', 'shortlisted'])->count(),
                'decided' => $applications->whereIn('status', ['accepted', 'rejected', 'waitlisted'])->count(),
                'needs_action' => $applications->where('status', 'needs_info')->count(),
            ],
            'applications' => $applications->take(5)->values(),
            'open_programs' => $openPrograms,
        ]);
    }
}
