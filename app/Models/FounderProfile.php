<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FounderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'default_country_code',
        'phone',
        'linkedin_url',
        'bio',
        'project_defaults',
    ];

    protected function casts(): array
    {
        return [
            'project_defaults' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
