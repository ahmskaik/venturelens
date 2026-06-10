<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'opens_at',
        'closes_at',
        'max_applications',
        'status',
        'rubric_id',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'max_applications' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isAcceptingApplications(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        $now = now();

        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }

        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        if ($this->max_applications) {
            $count = $this->applications()
                ->whereNotIn('status', ['draft'])
                ->count();

            if ($count >= $this->max_applications) {
                return false;
            }
        }

        return true;
    }

    public function resolveRubric(): Rubric
    {
        if ($this->rubric) {
            return $this->rubric;
        }

        $default = $this->organization->rubrics()->where('is_default', true)->first();

        if ($default) {
            return $default;
        }

        return $this->organization->rubrics()->firstOrFail();
    }
}
