<?php

namespace App\Http\Controllers\Founder;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $appliedProgramIds = $user->founderApplications()->pluck('program_id');

        $programs = Program::query()
            ->where('status', 'open')
            ->with('organization')
            ->orderBy('name')
            ->get()
            ->map(function (Program $program) use ($appliedProgramIds) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'slug' => $program->slug,
                    'description' => $program->description,
                    'organization' => $program->organization->name,
                    'accepting' => $program->isAcceptingApplications(),
                    'already_applied' => $appliedProgramIds->contains($program->id),
                    'apply_url' => route('apply.form', $program->slug),
                ];
            });

        return Inertia::render('Founder/Programs/Index', [
            'programs' => $programs,
        ]);
    }
}
