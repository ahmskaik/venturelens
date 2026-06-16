<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role');
    }

    public function primaryOrganization(): ?Organization
    {
        return $this->organizations()->first();
    }

    public function organizationRole(?Organization $organization = null): ?string
    {
        $organization ??= $this->primaryOrganization();

        if (! $organization) {
            return null;
        }

        return $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first()
            ?->pivot
            ?->role;
    }

    public function canManageOrganization(?Organization $organization = null): bool
    {
        return in_array($this->organizationRole($organization), ['owner', 'manager'], true);
    }

    public function isFounder(): bool
    {
        return $this->account_type === 'founder';
    }

    public function isIncubator(): bool
    {
        return $this->account_type === 'incubator';
    }

    public function homeRoute(): string
    {
        return $this->isFounder() ? 'founder.dashboard' : 'dashboard';
    }

    public function founderProfile(): HasOne
    {
        return $this->hasOne(FounderProfile::class);
    }

    public function founderApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'founder_user_id');
    }
}
