<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningResult extends Model
{
    protected $fillable = [
        'application_id',
        'model',
        'overall_score',
        'criterion_scores',
        'strengths',
        'weaknesses',
        'risk_flags',
        'summary',
        'recommendation',
        'raw_response',
        'error',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'overall_score' => 'decimal:2',
            'criterion_scores' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
            'risk_flags' => 'array',
            'raw_response' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'latency_ms' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function succeeded(): bool
    {
        return $this->error === null && $this->overall_score !== null;
    }
}
