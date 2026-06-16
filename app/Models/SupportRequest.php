<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'program_id',
        'subject',
        'question',
        'status',
        'ai_response',
        'sources',
        'confidence',
        'autonomy_level',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:3',
            'sources' => 'array',
            'autonomy_level' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
