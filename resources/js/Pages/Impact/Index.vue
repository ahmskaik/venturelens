<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
        <header class="border-b border-slate-800 bg-slate-900/80">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-xl font-bold text-indigo-400">VentureLens</Link>
                <nav class="flex gap-4 text-sm">
                    <Link href="/" class="text-slate-400 hover:text-white">Home</Link>
                    <Link href="/login" class="text-slate-400 hover:text-white">Log in</Link>
                    <a href="/api/v1/impact.json" class="text-indigo-400 hover:text-indigo-300" target="_blank">JSON API</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-12 space-y-10">
            <div>
                <p class="text-sm font-medium text-indigo-400">Live competition evidence · §3.3 scorecard</p>
                <h1 class="mt-2 text-4xl font-bold tracking-tight">Category Impact</h1>
                <p class="mt-2 text-slate-400">
                    Auto-computed from production data. Updated {{ metrics.generated_at?.slice(0, 19).replace('T', ' ') }} UTC.
                </p>
            </div>

            <!-- Business viability -->
            <section>
                <h2 class="text-lg font-semibold text-slate-300">Business viability</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Arms-length customers</p>
                        <p class="mt-1 text-3xl font-bold text-green-400">{{ metrics.business.arms_length_paying_customers }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Arms-length revenue</p>
                        <p class="mt-1 text-3xl font-bold text-green-400">${{ metrics.business.arms_length_revenue_usd.toLocaleString() }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Related-party revenue</p>
                        <p class="mt-1 text-3xl font-bold text-amber-400">${{ metrics.business.related_party_revenue_usd.toLocaleString() }}</p>
                        <p class="mt-1 text-xs text-slate-500">Reported separately per Devpost rules</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Subscription renewals</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.business.subscription_renewals }}</p>
                    </div>
                </div>
            </section>

            <!-- AI-native operations -->
            <section>
                <h2 class="text-lg font-semibold text-slate-300">AI-native operations</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-indigo-900 bg-indigo-950/50 p-5">
                        <p class="text-sm text-indigo-300">Decisions by AI (L2–L3)</p>
                        <p class="mt-1 text-4xl font-bold text-indigo-300">{{ metrics.ai_operations.pct_decisions_by_ai }}%</p>
                        <p class="mt-1 text-xs text-indigo-400/70">{{ metrics.ai_operations.ai_decided_actions }} / {{ metrics.ai_operations.total_agent_actions }} actions</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Human hours displaced</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.ai_operations.human_hours_displaced }}</p>
                        <p class="mt-1 text-xs text-slate-500">Σ agent human_minutes_saved / 60</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Gemini API calls</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.activity.gemini_api_calls.toLocaleString() }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Agent actions (total)</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.ai_operations.total_agent_actions }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                        <h3 class="font-medium">Autonomy distribution (L0–L3)</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, level) in metrics.ai_operations.autonomy_distribution" :key="level">
                                <div class="flex justify-between text-sm">
                                    <span>{{ autonomyLabels[level] || `L${level}` }}</span>
                                    <span class="text-slate-400">{{ count }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-800">
                                    <div class="h-2 rounded-full bg-indigo-500" :style="{ width: barWidth(count, autonomyMax) }" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                        <h3 class="font-medium">Actions by agent</h3>
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

            <!-- Category impact -->
            <section>
                <h2 class="text-lg font-semibold text-slate-300">Category impact</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Applications screened</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.activity.applications_screened }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Founder hours saved</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.impact.founder_hours_saved }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ metrics.assumptions.manual_review_minutes_per_app }} min/app manual review</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Programs enabled</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.activity.programs_enabled }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Countries reached</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.activity.countries_reached }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Founders w/ timely feedback</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.impact.founders_timely_feedback }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Jobs influenced (modeled)</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.impact.jobs_influenced_modeled }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ metrics.impact.accepted_startups }} accepted × {{ metrics.assumptions.avg_jobs_per_startup }} jobs/startup</p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
                        <p class="text-sm text-slate-500">Organizations registered</p>
                        <p class="mt-1 text-3xl font-bold">{{ metrics.activity.registered_organizations }}</p>
                    </div>
                </div>
            </section>

            <!-- Testimonials -->
            <section v-if="metrics.testimonials?.length" class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h2 class="font-semibold">Testimonials</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <blockquote v-for="(t, i) in metrics.testimonials" :key="i" class="rounded-lg border border-slate-800 bg-slate-950 p-4 text-sm">
                        <p class="text-slate-300">"{{ t.quote }}"</p>
                        <footer class="mt-2 text-slate-500">— {{ t.name }}, {{ t.role }}</footer>
                        <a v-if="t.url" :href="t.url" target="_blank" class="mt-1 inline-block text-xs text-indigo-400 hover:underline">Verify →</a>
                    </blockquote>
                </div>
            </section>

            <!-- Live agent feed -->
            <section class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <h2 class="font-semibold">Live agent feed</h2>
                <p class="mt-1 text-sm text-slate-500">Recent autonomous actions (read-only)</p>
                <div class="mt-4 max-h-80 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-900 text-left text-slate-500">
                            <tr>
                                <th class="pb-2 pr-4">Time</th>
                                <th class="pb-2 pr-4">Agent</th>
                                <th class="pb-2 pr-4">Decision</th>
                                <th class="pb-2">L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, i) in metrics.recent_agent_executions" :key="i" class="border-t border-slate-800">
                                <td class="py-2 pr-4 font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                                <td class="py-2 pr-4 capitalize">{{ e.agent_name }}</td>
                                <td class="py-2 pr-4">{{ e.decision }}</td>
                                <td class="py-2">L{{ e.autonomy_level }}</td>
                            </tr>
                            <tr v-if="!metrics.recent_agent_executions.length">
                                <td colspan="4" class="py-4 text-slate-500">No agent activity yet — run screening or agents:run-growth.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-600">
            VentureLens · Gemini XPRIZE · Evidence auto-generated from production DB
        </footer>
    </div>
</template>
