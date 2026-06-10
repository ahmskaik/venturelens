<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentExecution extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'organization_id',
        'agent_name',
        'step',
        'decision',
        'action_taken',
        'autonomy_level',
        'confidence',
        'human_minutes_saved',
        'status',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'autonomy_level' => 'integer',
            'confidence' => 'decimal:3',
            'human_minutes_saved' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
