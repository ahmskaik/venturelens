<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $this->organizationOrAbort($request);

        $programs = $organization->programs()
            ->withCount('applications')
            ->orderByDesc('opens_at')
            ->get()
            ->map(fn (Program $program) => $this->serializeProgram($program));

        return Inertia::render('Cohorts/Index', [
            'programs' => $programs,
            'can_manage_cohorts' => $request->user()->canManageOrganization($organization),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $organization = $this->organizationOrAbort($request);
        $validated = $request->validated();

        $slug = $this->resolveSlug($organization, $validated['slug'] ?? null, $validated['name']);

        Program::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'opens_at' => $this->parseDate($validated['opens_at'] ?? null, startOfDay: true),
            'closes_at' => $this->parseDate($validated['closes_at'] ?? null, startOfDay: false),
            'max_applications' => $validated['max_applications'] ?? null,
            'rubric_id' => $this->defaultRubricId($organization),
        ]);

        return redirect()->route('cohorts.index')->with('success', 'Cohort created.');
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->authorizeProgram($request, $program);
        $validated = $request->validated();

        $program->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'opens_at' => $this->parseDate($validated['opens_at'] ?? null, startOfDay: true),
            'closes_at' => $this->parseDate($validated['closes_at'] ?? null, startOfDay: false),
            'max_applications' => $validated['max_applications'] ?? null,
        ]);

        return redirect()->route('cohorts.index')->with('success', 'Cohort updated.');
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        $this->authorizeProgram($request, $program);
        abort_unless($request->user()->canManageOrganization($program->organization), 403);

        $applicationCount = $program->applications()->count();

        if ($applicationCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete a cohort with {$applicationCount} application(s). Archive it instead.",
            ]);
        }

        $program->delete();

        return redirect()->route('cohorts.index')->with('success', 'Cohort deleted.');
    }

    private function organizationOrAbort(Request $request): Organization
    {
        $organization = $request->user()?->primaryOrganization();

        abort_unless($organization, 404, 'No organization found for this user.');

        return $organization;
    }

    private function authorizeProgram(Request $request, Program $program): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        $belongs = $user->organizations()
            ->where('organizations.id', $program->organization_id)
            ->exists();

        abort_unless($belongs, 403);
    }

    private function serializeProgram(Program $program): array
    {
        return [
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
        ];
    }

    private function resolveSlug(Organization $organization, ?string $slug, string $name): string
    {
        $base = Str::slug($slug ?: $name);

        if ($base === '') {
            $base = 'cohort';
        }

        $candidate = $base;
        $suffix = 2;

        while ($organization->programs()->where('slug', $candidate)->exists()) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function defaultRubricId(Organization $organization): ?int
    {
        $rubric = $organization->rubrics()->where('is_default', true)->first()
            ?? $organization->rubrics()->first();

        if ($rubric) {
            return $rubric->id;
        }

        $rubric = Rubric::create([
            'organization_id' => $organization->id,
            'name' => 'Default screening rubric',
            'criteria' => Rubric::defaultCriteria(),
            'is_default' => true,
        ]);

        return $rubric->id;
    }

    private function parseDate(?string $value, bool $startOfDay): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = Carbon::parse($value);

        return $startOfDay ? $date->startOfDay() : $date->endOfDay();
    }
}
