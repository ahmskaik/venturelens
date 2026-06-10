<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->user()->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');

        $programs = $organization->programs()
            ->withCount('applications')
            ->orderByDesc('opens_at')
            ->get()
            ->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'slug' => $program->slug,
                'description' => $program->description,
                'status' => $program->status,
                'opens_at' => $program->opens_at?->toIso8601String(),
                'closes_at' => $program->closes_at?->toIso8601String(),
                'max_applications' => $program->max_applications,
                'applications_count' => $program->applications_count,
                'accepting' => $program->isAcceptingApplications(),
                'apply_url' => url("/apply/{$program->slug}"),
            ]);

        return Inertia::render('Cohorts/Index', [
            'programs' => $programs,
        ]);
    }
}
