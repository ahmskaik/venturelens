<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\User;
use App\Services\Agents\FinanceAgent;
use App\Services\Agents\SuccessAgent;
use App\Services\CompetitionMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        private readonly RevenueClassifier $revenueClassifier,
        private readonly FinanceAgent $financeAgent,
        private readonly SuccessAgent $successAgent,
    ) {}

    public function quotaForPlan(string $plan): int
    {
        return (int) config("venturelens.plans.{$plan}.quota", 5);
    }

    public function applyPlan(Organization $organization, string $plan, bool $additive = false): Organization
    {
        $quota = $this->quotaForPlan($plan);

        $organization->plan = $plan;

        if ($additive && $plan === 'cohort') {
            $organization->screenings_quota += $quota;
        } else {
            $organization->screenings_quota = max($organization->screenings_quota, $quota);
        }

        $organization->save();

        Log::info('billing.plan_applied', [
            'organization_id' => $organization->id,
            'plan' => $plan,
            'screenings_quota' => $organization->screenings_quota,
        ]);

        return $organization;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    public function fulfillCheckoutSession(Organization $organization, array $session, ?User $user = null): ?RevenueCharge
    {
        if (($session['payment_status'] ?? '') !== 'paid') {
            return null;
        }

        $sessionId = $session['id'] ?? null;
        if ($sessionId && RevenueCharge::where('stripe_checkout_session_id', $sessionId)->exists()) {
            return null;
        }

        $plan = $session['metadata']['plan'] ?? null;
        if (! in_array($plan, ['cohort', 'starter'], true)) {
            return null;
        }

        return DB::transaction(function () use ($organization, $session, $plan, $user, $sessionId) {
            $this->applyPlan($organization, $plan, additive: $plan === 'cohort');

            $amountCents = (int) ($session['amount_total'] ?? 0);
            $revenueType = $session['metadata']['revenue_type']
                ?? $this->revenueClassifier->classify($organization, $user);

            $charge = RevenueCharge::create([
                'organization_id' => $organization->id,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'amount_cents' => $amountCents,
                'currency' => $session['currency'] ?? 'usd',
                'plan' => $plan,
                'revenue_type' => $revenueType,
                'classification_source' => isset($session['metadata']['revenue_type']) ? 'checkout' : 'rule',
                'metadata' => [
                    'customer_email' => $session['customer_details']['email'] ?? null,
                    'mode' => $session['mode'] ?? null,
                ],
                'paid_at' => now(),
            ]);

            $this->financeAgent->recordStripeCharge($organization, $charge);
            $this->successAgent->recordPayment($organization, $charge);

            app(CompetitionMetrics::class)->forget();

            return $charge;
        });
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    public function recordInvoicePayment(Organization $organization, array $invoice): ?RevenueCharge
    {
        $invoiceId = $invoice['id'] ?? null;
        if (! $invoiceId || RevenueCharge::where('stripe_invoice_id', $invoiceId)->exists()) {
            return null;
        }

        $priceId = $invoice['lines']['data'][0]['price']['id'] ?? null;
        $plan = $this->planFromPriceId($priceId) ?? 'starter';

        $this->applyPlan($organization, $plan);

        $owner = $organization->users()->wherePivot('role', 'owner')->first();

        $charge = RevenueCharge::create([
            'organization_id' => $organization->id,
            'stripe_invoice_id' => $invoiceId,
            'stripe_subscription_id' => $invoice['subscription'] ?? null,
            'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
            'amount_cents' => (int) ($invoice['amount_paid'] ?? 0),
            'currency' => $invoice['currency'] ?? 'usd',
            'plan' => $plan,
            'revenue_type' => $this->revenueClassifier->classify($organization, $owner),
            'classification_source' => 'rule',
            'metadata' => ['billing_reason' => $invoice['billing_reason'] ?? null],
            'paid_at' => now(),
        ]);

        $this->financeAgent->recordStripeCharge($organization, $charge);
        $this->successAgent->recordPayment($organization, $charge);

        app(CompetitionMetrics::class)->forget();

        return $charge;
    }

    public function planFromPriceId(?string $priceId): ?string
    {
        if (! $priceId) {
            return null;
        }

        $prices = config('venturelens.stripe.prices', []);

        foreach ($prices as $plan => $configured) {
            if ($configured && $configured === $priceId) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @return array{arms_length_cents: int, related_party_cents: int, total_cents: int}
     */
    public function revenueSummary(?Organization $organization = null): array
    {
        $query = RevenueCharge::query();

        if ($organization) {
            $query->where('organization_id', $organization->id);
        }

        $armsLength = (clone $query)->where('revenue_type', 'arms_length')->sum('amount_cents');
        $related = (clone $query)->where('revenue_type', 'related_party')->sum('amount_cents');

        return [
            'arms_length_cents' => (int) $armsLength,
            'related_party_cents' => (int) $related,
            'total_cents' => (int) $armsLength + (int) $related,
        ];
    }
}
