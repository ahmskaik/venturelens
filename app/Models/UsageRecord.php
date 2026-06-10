<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'gemini_calls',
        'tokens',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'gemini_calls' => 'integer',
            'tokens' => 'integer',
            'recorded_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
