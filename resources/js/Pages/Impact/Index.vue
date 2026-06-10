<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../../Components/Brand/Logo.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

const props = defineProps({
    metrics: Object,
});

const autonomyLabels = {
    0: 'L0 Observe',
    1: 'L1 Suggest',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};

const autonomyMax = computed(() => {
    const dist = props.metrics.ai_operations.autonomy_distribution;
    return Math.max(...Object.values(dist), 1);
});

const agentMax = computed(() => {
    const byAgent = props.metrics.ai_operations.by_agent;
    return Math.max(...Object.values(byAgent), 1);
});

function barWidth(count, max) {
    return `${Math.round((count / max) * 100)}%`;
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/80 bg-slate-900/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Logo dark />
                <nav class="flex items-center gap-4 text-sm">
                    <GeminiBadge />
                    <Link href="/" class="text-slate-400 transition hover:text-white">Home</Link>
                    <Link href="/login" class="text-slate-400 transition hover:text-white">Log in</Link>
                    <Link href="/dashboard" class="text-slate-400 transition hover:text-white">Dashboard</Link>
                    <a href="/api/v1/impact.json" class="font-medium text-violet-400 hover:text-violet-300" target="_blank">JSON API</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-12 space-y-12">
            <div class="relative overflow-hidden rounded-3xl border border-violet-500/20 bg-gradient-to-br from-violet-950/80 via-slate-900 to-slate-950 p-10">
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-600/20 blur-3xl" />
                <p class="text-sm font-semibold uppercase tracking-wider text-violet-400">Live competition evidence</p>
                <h1 class="vl-display mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Category Impact</h1>
                <p class="mt-3 max-w-2xl text-slate-400">
                    Auto-computed from production data. Updated {{ metrics.generated_at?.slice(0, 19).replace('T', ' ') }} UTC.
                </p>
            </div>

            <section>
                <h2 class="vl-display text-lg font-bold text-slate-300">Business viability</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-emerald-900/50 bg-emerald-950/30 p-5">
                        <p class="text-sm text-emerald-400/80">Arms-length customers</p>
                        <p class="vl-display mt-2 text-4xl font-bold text-emerald-400">{{ metrics.business.arms_length_paying_customers }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-900/50 bg-emerald-950/30 p-5">
                        <p class="text-sm text-emerald-400/80">Arms-length revenue</p>
                        <p class="vl-display mt-2 text-4xl font-bold text-emerald-400">${{ metrics.business.arms_length_revenue_usd.toLocaleString() }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-900/50 bg-amber-950/20 p-5">
                        <p class="text-sm text-amber-400/80">Related-party revenue</p>
                        <p class="vl-display mt-2 text-4xl font-bold text-amber-400">${{ metrics.business.related_party_revenue_usd.toLocaleString() }}</p>
                        <p class="mt-2 text-xs text-slate-500">Reported separately per Devpost rules</p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                        <p class="text-sm text-slate-500">Subscription renewals</p>
                        <p class="vl-display mt-2 text-4xl font-bold">{{ metrics.business.subscription_renewals }}</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="vl-display text-lg font-bold text-slate-300">AI-native operations</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-violet-800/60 bg-violet-950/40 p-5 ring-1 ring-violet-500/20">
                        <p class="text-sm text-violet-300">Decisions by AI (L2–L3)</p>
                        <p class="vl-display mt-2 text-5xl font-bold text-violet-300">{{ metrics.ai_operations.pct_decisions_by_ai }}%</p>
                        <p class="mt-2 text-xs text-violet-400/70">{{ metrics.ai_operations.ai_decided_actions }} / {{ metrics.ai_operations.total_agent_actions }} actions</p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                        <p class="text-sm text-slate-500">Human hours displaced</p>
                        <p class="vl-display mt-2 text-4xl font-bold">{{ metrics.ai_operations.human_hours_displaced }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                        <p class="text-sm text-slate-500">Gemini API calls</p>
                        <p class="vl-display mt-2 text-4xl font-bold">{{ metrics.activity.gemini_api_calls.toLocaleString() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                        <p class="text-sm text-slate-500">Agent actions (total)</p>
                        <p class="vl-display mt-2 text-4xl font-bold">{{ metrics.ai_operations.total_agent_actions }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                        <h3 class="font-semibold">Autonomy distribution (L0–L3)</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, level) in metrics.ai_operations.autonomy_distribution" :key="level">
                                <div class="flex justify-between text-sm">
                                    <span>{{ autonomyLabels[level] || `L${level}` }}</span>
                                    <span class="text-slate-400">{{ count }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-800">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-violet-500 to-brand-500" :style="{ width: barWidth(count, autonomyMax) }" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                        <h3 class="font-semibold">Actions by agent</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, agent) in metrics.ai_operations.by_agent" :key="agent">
                                <div class="flex justify-between text-sm capitalize">
                                    <span>{{ agent || 'unknown' }}</span>
                                    <span class="text-slate-400">{{ count }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-800">
                                    <div class="h-2 rounded-full bg-emerald-500" :style="{ width: barWidth(count, agentMax) }" />
                                </div>
                            </div>
                            <p v-if="!Object.keys(metrics.ai_operations.by_agent).length" class="text-sm text-slate-500">No agent executions yet.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="vl-display text-lg font-bold text-slate-300">Category impact</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="(item, i) in [
                        { label: 'Applications screened', value: metrics.activity.applications_screened },
                        { label: 'Founder hours saved', value: metrics.impact.founder_hours_saved, hint: `${metrics.assumptions.manual_review_minutes_per_app} min/app manual review` },
                        { label: 'Programs enabled', value: metrics.activity.programs_enabled },
                        { label: 'Countries reached', value: metrics.activity.countries_reached },
                        { label: 'Founders w/ timely feedback', value: metrics.impact.founders_timely_feedback },
                        { label: 'Jobs influenced (modeled)', value: metrics.impact.jobs_influenced_modeled, hint: `${metrics.impact.accepted_startups} accepted × ${metrics.assumptions.avg_jobs_per_startup} jobs/startup` },
                        { label: 'Organizations registered', value: metrics.activity.registered_organizations },
                    ]" :key="i" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
                        <p class="text-sm text-slate-500">{{ item.label }}</p>
                        <p class="vl-display mt-2 text-3xl font-bold">{{ item.value }}</p>
                        <p v-if="item.hint" class="mt-1 text-xs text-slate-500">{{ item.hint }}</p>
                    </div>
                </div>
            </section>

            <section v-if="metrics.testimonials?.length" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="vl-display font-bold">Testimonials</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <blockquote v-for="(t, i) in metrics.testimonials" :key="i" class="rounded-xl border border-slate-800 bg-slate-950 p-5 text-sm">
                        <p class="text-slate-300">"{{ t.quote }}"</p>
                        <footer class="mt-3 text-slate-500">— {{ t.name }}, {{ t.role }}</footer>
                        <a v-if="t.url" :href="t.url" target="_blank" class="mt-2 inline-block text-xs text-violet-400 hover:underline">Verify →</a>
                    </blockquote>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="vl-display font-bold">Live agent feed</h2>
                <p class="mt-1 text-sm text-slate-500">Recent autonomous actions (read-only)</p>
                <div class="mt-4 max-h-80 overflow-auto rounded-xl border border-slate-800">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-900 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Time</th>
                                <th class="px-4 py-3">Agent</th>
                                <th class="px-4 py-3">Decision</th>
                                <th class="px-4 py-3">L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, i) in metrics.recent_agent_executions" :key="i" class="border-t border-slate-800 hover:bg-slate-800/50">
                                <td class="px-4 py-2 font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                                <td class="px-4 py-2 capitalize">{{ e.agent_name }}</td>
                                <td class="px-4 py-2">{{ e.decision }}</td>
                                <td class="px-4 py-2">L{{ e.autonomy_level }}</td>
                            </tr>
                            <tr v-if="!metrics.recent_agent_executions.length">
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">No agent activity yet — run screening or agents:run-growth.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-800 py-8 text-center">
            <div class="flex justify-center">
                <Logo dark />
            </div>
            <p class="mt-4 text-xs text-slate-600">Build with Gemini XPRIZE · Evidence auto-generated from production DB</p>
        </footer>
    </div>
</template>
