<?php

namespace App\Support;

use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ApplicationListQuery
{
  /** @var list<string> */
    public const STATUSES = [
        'submitted',
        'processing',
        'screened',
        'needs_info',
        'shortlisted',
        'accepted',
        'rejected',
        'waitlisted',
    ];

  /** @var list<string> */
    public const RECOMMENDATIONS = [
        'shortlist',
        'reject',
        'needs_review',
    ];

  /** @var array<string, string> */
    public const SORTS = [
        'submitted_desc' => 'Newest first',
        'submitted_asc' => 'Oldest first',
        'score_desc' => 'Highest AI score',
        'score_asc' => 'Lowest AI score',
        'name_asc' => 'Startup A–Z',
    ];

    public function __construct(
        private Builder $query,
        private Request $request,
    ) {}

    /**
     * @param  list<int|string>  $programIds
     */
    public static function forOrganization(array $programIds, Request $request): self
    {
        $query = Application::query()
            ->whereIn('program_id', $programIds)
            ->with(['program', 'latestScreeningResult']);

        return new self($query, $request);
    }

    public static function forProgram(int $programId, Request $request): self
    {
        $query = Application::query()
            ->where('program_id', $programId)
            ->with('latestScreeningResult');

        return new self($query, $request);
    }

    public function applyFilters(): self
    {
        $search = trim((string) $this->request->input('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $this->query->where(function (Builder $inner) use ($like) {
                $inner->where('startup_name', 'like', $like)
                    ->orWhere('founder_name', 'like', $like)
                    ->orWhere('founder_email', 'like', $like);
            });
        }

        if ($status = $this->request->input('status')) {
            $this->query->where('status', $status);
        }

        if ($programId = $this->request->integer('program')) {
            $this->query->where('program_id', $programId);
        }

        if ($sector = $this->request->input('sector')) {
            $this->query->where('sector', $sector);
        }

        if ($country = $this->request->input('country')) {
            $this->query->where('country_code', $country);
        }

        if ($recommendation = $this->request->input('recommendation')) {
            $this->query->whereHas(
                'latestScreeningResult',
                fn (Builder $q) => $q->where('recommendation', $recommendation),
            );
        }

        if ($this->request->boolean('screened_only')) {
            $this->query->whereNotNull('ai_overall_score');
        }

        return $this;
    }

    public function applySort(): self
    {
        $sort = (string) $this->request->input('sort', 'submitted_desc');

        match ($sort) {
            'submitted_asc' => $this->query->orderBy('submitted_at'),
            'score_desc' => $this->query->orderByDesc('ai_overall_score'),
            'score_asc' => $this->query->orderBy('ai_overall_score'),
            'name_asc' => $this->query->orderBy('startup_name'),
            default => $this->query->orderByDesc('submitted_at'),
        };

        return $this;
    }

    public function paginate(int $defaultPerPage = 25): LengthAwarePaginator
    {
        $perPage = min(100, max(10, $this->request->integer('per_page', $defaultPerPage)));

        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'q' => (string) $this->request->input('q', ''),
            'status' => (string) $this->request->input('status', ''),
            'program' => (string) $this->request->input('program', ''),
            'sector' => (string) $this->request->input('sector', ''),
            'country' => (string) $this->request->input('country', ''),
            'recommendation' => (string) $this->request->input('recommendation', ''),
            'screened_only' => $this->request->boolean('screened_only'),
            'sort' => (string) $this->request->input('sort', 'submitted_desc'),
            'per_page' => $this->request->integer('per_page', 25),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapApplication(Application $app, bool $includeProgram = true): array
    {
        $row = [
            'id' => $app->id,
            'startup_name' => $app->startup_name,
            'founder_name' => $app->founder_name,
            'status' => $app->status,
            'ai_overall_score' => $app->ai_overall_score,
            'submitted_at' => $app->submitted_at?->toIso8601String(),
            'recommendation' => $app->latestScreeningResult?->recommendation,
            'sector' => $app->sector,
            'country_code' => $app->country_code,
        ];

        if ($includeProgram && $app->relationLoaded('program')) {
            $row['program'] = [
                'id' => $app->program->id,
                'name' => $app->program->name,
            ];
        }

        return $row;
    }

    /**
     * @param  list<int|string>  $programIds
     * @return array<string, mixed>
     */
    public static function filterOptionsForPrograms(array $programIds): array
    {
        $base = Application::query()->whereIn('program_id', $programIds);

        return [
            'statuses' => self::STATUSES,
            'recommendations' => self::RECOMMENDATIONS,
            'sorts' => self::SORTS,
            'sectors' => (clone $base)->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector')->values()->all(),
            'countries' => CountryCodes::optionsForCodes(
                (clone $base)->whereNotNull('country_code')->distinct()->orderBy('country_code')->pluck('country_code'),
            ),
        ];
    }
}
