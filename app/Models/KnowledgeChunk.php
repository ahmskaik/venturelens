<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'organization_id',
        'program_id',
        'chunk_key',
        'source_type',
        'source_id',
        'title',
        'content',
        'content_hash',
        'embedding',
        'dimensions',
        'embedding_model',
        'metadata',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
            'embedded_at' => 'datetime',
            'dimensions' => 'integer',
            'source_id' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
