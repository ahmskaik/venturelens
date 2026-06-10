<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueCharge extends Model
{
    protected $fillable = [
        'organization_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_invoice_id',
        'stripe_subscription_id',
        'amount_cents',
        'currency',
        'plan',
        'revenue_type',
        'classification_source',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function amountUsd(): float
    {
        return $this->amount_cents / 100;
    }
}
