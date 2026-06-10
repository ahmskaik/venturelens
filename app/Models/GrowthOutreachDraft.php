<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrowthOutreachDraft extends Model
{
    protected $fillable = [
        'target_organization',
        'target_contact_email',
        'target_country',
        'channel',
        'subject',
        'body',
        'status',
        'autonomy_level',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'autonomy_level' => 'integer',
            'metadata' => 'array',
        ];
    }
}
