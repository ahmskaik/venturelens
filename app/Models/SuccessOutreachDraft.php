<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessOutreachDraft extends Model
{
    protected $fillable = [
        'organization_id',
        'revenue_charge_id',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function revenueCharge(): BelongsTo
    {
        return $this->belongsTo(RevenueCharge::class);
    }
}
