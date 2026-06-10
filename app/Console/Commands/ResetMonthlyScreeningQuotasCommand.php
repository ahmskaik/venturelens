<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetMonthlyScreeningQuotasCommand extends Command
{
    protected $signature = 'billing:reset-monthly-quotas';

    protected $description = 'Reset screenings_used for monthly subscription plans (Starter, Pro)';

    public function handle(BillingService $billing): int
    {
        $plans = ['starter', 'pro'];
        $reset = 0;

        Organization::whereIn('plan', $plans)->each(function (Organization $org) use ($billing, &$reset) {
            $org->screenings_used = 0;
            $org->screenings_quota = $billing->quotaForPlan($org->plan);
            $org->save();
            $reset++;
        });

        Log::info('billing.monthly_quota_reset', ['organizations' => $reset]);
        $this->info("Reset screening usage for {$reset} organization(s).");

        return self::SUCCESS;
    }
}
