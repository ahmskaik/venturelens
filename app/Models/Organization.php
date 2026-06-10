<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    use Billable;

    protected $fillable = [
        'name',
        'slug',
        'country_code',
        'website',
        'plan',
        'screenings_quota',
        'screenings_used',
    ];

    protected function casts(): array
    {
        return [
            'screenings_quota' => 'integer',
            'screenings_used' => 'integer',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function rubrics(): HasMany
    {
        return $this->hasMany(Rubric::class);
    }

    public function agentExecutions(): HasMany
    {
        return $this->hasMany(AgentExecution::class);
    }

    public function revenueCharges(): HasMany
    {
        return $this->hasMany(RevenueCharge::class);
    }

    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }

    public function hasScreeningQuota(): bool
    {
        return $this->screenings_used < $this->screenings_quota;
    }

    public function incrementScreeningsUsed(): void
    {
        $this->increment('screenings_used');
    }

    public function stripeEmail(): ?string
    {
        return $this->users()->wherePivot('role', 'owner')->value('email');
    }

    public function stripeName(): ?string
    {
        return $this->name;
    }
}
