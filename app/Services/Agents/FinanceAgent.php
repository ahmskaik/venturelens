<?php

namespace App\Services\Agents;

use App\Models\AgentExecution;
use App\Models\Organization;
use App\Models\RevenueCharge;

class FinanceAgent implements BusinessAgentInterface
{
    public function __construct(
        private readonly AgentRegistry $registry,
    ) {}

    public function name(): string
    {
        return 'finance';
    }

    /**
     * Log a Stripe charge classification to agent_executions (L3 autonomous rule engine).
     */
    public function recordStripeCharge(Organization $organization, RevenueCharge $charge): ?AgentResult
    {
        if (! $this->registry->isEnabled($this->name())) {
            return null;
        }

        if ($this->alreadyLogged($charge)) {
            return null;
        }

        $isArmsLength = $charge->revenue_type === 'arms_length';
        $decision = $isArmsLength ? 'classify_arms_length' : 'classify_related_party';
        $amountUsd = number_format($charge->amount_cents / 100, 2);

        $result = new AgentResult(
            decision: $decision,
            actionTaken: sprintf(
                'Auto-classified Stripe %s charge ($%s) as %s (source: %s)',
                $charge->plan,
                $amountUsd,
                str_replace('_', '-', $charge->revenue_type),
                $charge->classification_source,
            ),
            autonomyLevel: 3,
            confidence: 0.99,
            humanMinutesSaved: 5,
            metadata: [
                'revenue_charge_id' => $charge->id,
                'revenue_type' => $charge->revenue_type,
                'amount_cents' => $charge->amount_cents,
                'plan' => $charge->plan,
                'classification_source' => $charge->classification_source,
                'stripe_checkout_session_id' => $charge->stripe_checkout_session_id,
                'stripe_invoice_id' => $charge->stripe_invoice_id,
            ],
        );

        $this->registry->logExecution($organization, $this, $result, step: 'stripe_reconcile');

        return $result;
    }

    public function run(): AgentResult
    {
        if (! $this->registry->canRun($this->name())) {
            return new AgentResult(
                decision: 'rate_limited',
                actionTaken: 'Finance agent daily cap reached or agent disabled',
                autonomyLevel: 0,
                status: 'failed',
            );
        }

        $loggedChargeIds = AgentExecution::query()
            ->where('agent_name', $this->name())
            ->where('step', 'stripe_reconcile')
            ->get()
            ->map(fn (AgentExecution $e) => $e->metadata['revenue_charge_id'] ?? null)
            ->filter()
            ->all();

        $charges = RevenueCharge::with('organization')
            ->when($loggedChargeIds !== [], fn ($q) => $q->whereNotIn('id', $loggedChargeIds))
            ->latest('paid_at')
            ->limit(20)
            ->get();

        $reconciled = 0;
        foreach ($charges as $charge) {
            if ($charge->organization && $this->recordStripeCharge($charge->organization, $charge)) {
                $reconciled++;
            }
        }

        return new AgentResult(
            decision: $reconciled > 0 ? 'reconcile_batch' : 'idle',
            actionTaken: $reconciled > 0
                ? "Backfilled finance logs for {$reconciled} Stripe charge(s)"
                : 'All recent Stripe charges already reconciled',
            autonomyLevel: 3,
            humanMinutesSaved: $reconciled * 5,
            metadata: ['reconciled_count' => $reconciled],
        );
    }

    private function alreadyLogged(RevenueCharge $charge): bool
    {
        return AgentExecution::query()
            ->where('agent_name', $this->name())
            ->where('step', 'stripe_reconcile')
            ->where('metadata->revenue_charge_id', $charge->id)
            ->exists();
    }
}
