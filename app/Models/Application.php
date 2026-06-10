<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Application extends Model
{
    protected $fillable = [
        'program_id',
        'startup_name',
        'founder_name',
        'founder_email',
        'country_code',
        'stage',
        'sector',
        'form_data',
        'status',
        'ai_overall_score',
        'manual_overall_score',
        'decision_by',
        'decision_at',
        'submitted_at',
        'status_token',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
            'ai_overall_score' => 'decimal:2',
            'manual_overall_score' => 'decimal:2',
            'decision_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Application $application) {
            if (! $application->status_token) {
                $application->status_token = Str::random(48);
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }

    public function screeningResults(): HasMany
    {
        return $this->hasMany(ScreeningResult::class);
    }

    public function latestScreeningResult(): HasOne
    {
        return $this->hasOne(ScreeningResult::class)->latestOfMany();
    }

    public function agentExecutions(): HasMany
    {
        return $this->hasMany(AgentExecution::class);
    }

    public function markSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }
}
