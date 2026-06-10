<?php

namespace App\Services;

use App\Models\AgentExecution;
use App\Models\Application;
use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\ScreeningResult;
use App\Models\UsageRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Computes §3.3 scorecard KPIs for /impact and evidence pack.
 *
 * Formulas (§17.4 / Appendix E.2):
 *   founder_hours_saved   = applications_screened × manual_review_minutes_per_app / 60
 *   human_hours_displaced = Σ(agent_executions.human_minutes_saved) / 60
 *   pct_decisions_ai      = actions at L2–L3 / total agent operational actions
 *   jobs_influenced       = accepted_startups × avg_jobs_per_startup (modeled)
 */
class CompetitionMetrics
{
    private const CACHE_KEY = 'venturelens.competition_metrics';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->compute());
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function compute(): array
    {
        $applicationsScreened = ScreeningResult::whereNull('error')->count();
        $manualMinutes = (int) config('venturelens.impact.manual_review_minutes_per_app', 45);
        $founderHoursSaved = round($applicationsScreened * $manualMinutes / 60, 1);

        $agentQuery = AgentExecution::query();
        $totalAgentActions = (clone $agentQuery)->count();
        $aiDecidedActions = (clone $agentQuery)->whereIn('autonomy_level', [2, 3])->count();
        $humanMinutesSaved = (int) (clone $agentQuery)->sum('human_minutes_saved');
        $humanHoursDisplaced = round($humanMinutesSaved / 60, 1);

        $pctDecisionsAi = $totalAgentActions > 0
            ? round(($aiDecidedActions / $totalAgentActions) * 100, 1)
            : 0.0;

        $autonomyDistribution = AgentExecution::query()
            ->select('autonomy_level', DB::raw('count(*) as count'))
            ->groupBy('autonomy_level')
            ->pluck('count', 'autonomy_level')
            ->mapWithKeys(fn ($count, $level) => [(string) $level => (int) $count])
            ->all();

        for ($level = 0; $level <= 3; $level++) {
            $autonomyDistribution[(string) $level] ??= 0;
        }
        ksort($autonomyDistribution);

        $byAgent = AgentExecution::query()
            ->select('agent_name', DB::raw('count(*) as count'))
            ->groupBy('agent_name')
            ->pluck('count', 'agent_name')
            ->map(fn ($count) => (int) $count)
            ->all();

        $armsLengthRevenueCents = RevenueCharge::where('revenue_type', 'arms_length')->sum('amount_cents');
        $relatedPartyRevenueCents = RevenueCharge::where('revenue_type', 'related_party')->sum('amount_cents');
        $armsLengthCustomers = RevenueCharge::where('revenue_type', 'arms_length')
            ->distinct('organization_id')
            ->count('organization_id');

        $geminiCalls = (int) UsageRecord::sum('gemini_calls');
        $geminiTokens = (int) UsageRecord::sum('tokens');

        $organizationsCount = Organization::count();
        $countriesReached = Organization::whereNotNull('country_code')
            ->distinct('country_code')
            ->count('country_code');

        $programsEnabled = Organization::whereIn('plan', ['cohort', 'starter', 'pro'])
            ->orWhereHas('revenueCharges')
            ->count();

        $acceptedStartups = Application::whereIn('status', ['accepted', 'approved'])->count();
        $avgJobs = (float) config('venturelens.impact.avg_jobs_per_startup', 3);
        $jobsInfluenced = (int) round($acceptedStartups * $avgJobs);

        $timelyFeedback = Application::whereNotNull('submitted_at')
            ->whereHas('screeningResults', fn ($q) => $q->whereNull('error'))
            ->count();

        $renewals = RevenueCharge::where('plan', 'starter')
            ->whereNotNull('stripe_invoice_id')
            ->distinct('organization_id')
            ->count('organization_id');

        $recentExecutions = AgentExecution::query()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (AgentExecution $e) => [
                'agent_name' => $e->agent_name,
                'step' => $e->step,
                'decision' => $e->decision,
                'action_taken' => $e->action_taken,
                'autonomy_level' => $e->autonomy_level,
                'status' => $e->status,
                'created_at' => $e->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'generated_at' => now()->toIso8601String(),
            'assumptions' => [
                'manual_review_minutes_per_app' => $manualMinutes,
                'avg_jobs_per_startup' => $avgJobs,
                'jobs_influenced_note' => 'Modeled: accepted_startups × avg_jobs_per_startup',
            ],
            'business' => [
                'arms_length_paying_customers' => $armsLengthCustomers,
                'arms_length_revenue_usd' => round($armsLengthRevenueCents / 100, 2),
                'related_party_revenue_usd' => round($relatedPartyRevenueCents / 100, 2),
                'total_revenue_usd' => round(($armsLengthRevenueCents + $relatedPartyRevenueCents) / 100, 2),
                'subscription_renewals' => $renewals,
            ],
            'activity' => [
                'applications_screened' => $applicationsScreened,
                'gemini_api_calls' => $geminiCalls,
                'gemini_tokens' => $geminiTokens,
                'registered_organizations' => $organizationsCount,
                'programs_enabled' => $programsEnabled,
                'countries_reached' => $countriesReached,
            ],
            'ai_operations' => [
                'total_agent_actions' => $totalAgentActions,
                'ai_decided_actions' => $aiDecidedActions,
                'pct_decisions_by_ai' => $pctDecisionsAi,
                'autonomy_distribution' => $autonomyDistribution,
                'by_agent' => $byAgent,
                'human_hours_displaced' => $humanHoursDisplaced,
            ],
            'impact' => [
                'founder_hours_saved' => $founderHoursSaved,
                'founders_timely_feedback' => $timelyFeedback,
                'jobs_influenced_modeled' => $jobsInfluenced,
                'accepted_startups' => $acceptedStartups,
            ],
            'testimonials' => config('venturelens.impact.testimonials', []),
            'recent_agent_executions' => $recentExecutions,
            'scorecard_floors' => config('venturelens.impact.scorecard_floors', []),
        ];
    }
}
