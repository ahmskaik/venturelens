<script setup>
import KpiCard from '../components/KpiCard.vue';
import AgentBarChart from '../components/AgentBarChart.vue';
import { formatUsd, formatNum } from '../utils/format';

defineProps({
    metrics: { type: Object, required: true },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
});

defineEmits(['retry']);
</script>

<template>
    <div v-if="loading && !metrics" class="py-16 text-center text-slate-500">Loading live impact metrics…</div>

    <div v-else-if="error && !metrics" class="vl-card mx-auto max-w-md p-6 text-center">
        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
        <button
            type="button"
            class="mt-4 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
            @click="$emit('retry')"
        >
            Retry
        </button>
    </div>

    <template v-else-if="metrics">
        <div class="border-b border-slate-200 pb-6 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Updated {{ metrics.generated_at?.slice(0, 19).replace('T', ' ') }} UTC
            </p>
        </div>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Business viability</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard label="Arms-length revenue" :value="formatUsd(metrics.business?.arms_length_revenue_usd)" />
                <KpiCard label="Paying customers" :value="formatNum(metrics.business?.arms_length_paying_customers)" />
                <KpiCard label="Total revenue" :value="formatUsd(metrics.business?.total_revenue_usd)" />
                <KpiCard label="Related-party revenue" :value="formatUsd(metrics.business?.related_party_revenue_usd)" />
            </div>
        </section>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Activity</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard label="Applications screened" :value="formatNum(metrics.activity?.applications_screened)" />
                <KpiCard label="Gemini API calls" :value="formatNum(metrics.activity?.gemini_api_calls)" />
                <KpiCard label="Organizations" :value="formatNum(metrics.activity?.registered_organizations)" />
                <KpiCard label="Countries reached" :value="formatNum(metrics.activity?.countries_reached)" />
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">AI operations</h2>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <KpiCard label="Total agent actions" :value="formatNum(metrics.ai_operations?.total_agent_actions)" />
                    <KpiCard label="Decisions by AI" :value="formatNum(metrics.ai_operations?.pct_decisions_by_ai, 1) + '%'" />
                    <KpiCard label="Human hours displaced" :value="formatNum(metrics.ai_operations?.human_hours_displaced, 1)" />
                </div>
            </div>
            <AgentBarChart :by-agent="metrics.ai_operations?.by_agent ?? {}" />
        </section>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Category impact</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <KpiCard label="Founder hours saved" :value="formatNum(metrics.impact?.founder_hours_saved, 1)" />
                <KpiCard label="Accepted startups" :value="formatNum(metrics.impact?.accepted_startups)" />
                <KpiCard label="Jobs influenced (modeled)" :value="formatNum(metrics.impact?.jobs_influenced_modeled)" />
            </div>
        </section>
    </template>
</template>
