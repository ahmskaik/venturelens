<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'program_id',
        'sources',
        'confidence',
        'prompt_tokens',
        'completion_tokens',
        'retrieval_ms',
        'generation_ms',
        'latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'confidence' => 'decimal:3',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'retrieval_ms' => 'integer',
            'generation_ms' => 'integer',
            'latency_ms' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
