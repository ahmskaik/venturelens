<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAgent extends Model
{
    protected $fillable = [
        'name',
        'enabled',
        'autonomy_level',
        'daily_action_cap',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'autonomy_level' => 'integer',
            'daily_action_cap' => 'integer',
            'config' => 'array',
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
