<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../../Components/Brand/Logo.vue';
import SeoHead from '../../Components/Seo/SeoHead.vue';
import { buildImpactJsonLd, seoDefaults } from '../../seo/defaults.js';

const props = defineProps({
    metrics: Object,
    archivedSnapshots: {
        type: Array,
        default: () => [],
    },
    gcsBucket: {
        type: String,
        default: null,
    },
});

const page = usePage();
const impactJsonLd = computed(() => buildImpactJsonLd(page.props.seo?.appUrl ?? '', page.props.seo ?? {}));

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
    <SeoHead
        :title="seoDefaults.impact.title"
        :description="seoDefaults.impact.description"
        :keywords="seoDefaults.impact.keywords"
        url="/impact"
        :json-ld="impactJsonLd"
    />
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Logo />
                <nav class="flex items-center gap-3 text-sm">
                    <Link href="/" class="vl-btn-ghost">Home</Link>
                    <Link href="/login" class="vl-btn-ghost">Sign in</Link>
                    <Link href="/dashboard" class="vl-btn-ghost">Dashboard</Link>
                    <a href="/api/v1/impact.json" class="text-sm font-medium text-brand-600 hover:text-brand-700" target="_blank">JSON API</a>
                    <a href="/widgets/impact/" class="text-sm font-medium text-brand-600 hover:text-brand-700" target="_blank">Embed widget</a>
                    <a href="/evidence-explorer/" class="text-sm font-medium text-brand-600 hover:text-brand-700" target="_blank">Evidence explorer</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl space-y-10 px-6 py-10">
            <div class="border-b border-slate-200 pb-8">
                <p class="text-sm font-medium text-slate-500">Live metrics</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Impact report</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Auto-computed from production data. Updated {{ metrics.generated_at?.slice(0, 19).replace('T', ' ') }} UTC.
                </p>
            </div>

            <section>
                <h2 class="text-base font-semibold text-slate-900">Business viability</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Arms-length customers</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.business.arms_length_paying_customers }}</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Arms-length revenue</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">${{ metrics.business.arms_length_revenue_usd.toLocaleString() }}</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Related-party revenue</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">${{ metrics.business.related_party_revenue_usd.toLocaleString() }}</p>
                        <p class="mt-2 text-xs text-slate-400">Reported separately per competition rules</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Subscription renewals</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.business.subscription_renewals }}</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-base font-semibold text-slate-900">AI operations</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="vl-card border-l-4 border-l-brand-500 p-5">
                        <p class="text-sm text-slate-500">Decisions by AI (L2–L3)</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.ai_operations.pct_decisions_by_ai }}%</p>
                        <p class="mt-2 text-xs text-slate-400">{{ metrics.ai_operations.ai_decided_actions }} / {{ metrics.ai_operations.total_agent_actions }} actions</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Human hours displaced</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.ai_operations.human_hours_displaced }}</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Gemini API calls</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.activity.gemini_api_calls.toLocaleString() }}</p>
                    </div>
                    <div class="vl-card p-5">
                        <p class="text-sm text-slate-500">Agent actions (total)</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-900">{{ metrics.ai_operations.total_agent_actions }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="vl-card p-6">
                        <h3 class="text-sm font-semibold text-slate-900">Autonomy distribution (L0–L3)</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, level) in metrics.ai_operations.autonomy_distribution" :key="level">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-700">{{ autonomyLabels[level] || `L${level}` }}</span>
                                    <span class="text-slate-500">{{ count }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-brand-600" :style="{ width: barWidth(count, autonomyMax) }" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="vl-card p-6">
                        <h3 class="text-sm font-semibold text-slate-900">Actions by agent</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, agent) in metrics.ai_operations.by_agent" :key="agent">
                                <div class="flex justify-between text-sm capitalize">
                                    <span class="text-slate-700">{{ agent || 'unknown' }}</span>
                                    <span class="text-slate-500">{{ count }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-slate-600" :style="{ width: barWidth(count, agentMax) }" />
                                </div>
                            </div>
                            <p v-if="!Object.keys(metrics.ai_operations.by_agent).length" class="text-sm text-slate-500">No agent executions yet.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-base font-semibold text-slate-900">Category impact</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(item, i) in [
                            { label: 'Applications screened', value: metrics.activity.applications_screened },
                            { label: 'Founder hours saved', value: metrics.impact.founder_hours_saved, hint: `${metrics.assumptions.manual_review_minutes_per_app} min/app manual review` },
                            { label: 'Programs enabled', value: metrics.activity.programs_enabled },
                            { label: 'Countries reached', value: metrics.activity.countries_reached },
                            { label: 'Founders w/ timely feedback', value: metrics.impact.founders_timely_feedback },
                            { label: 'Jobs influenced (modeled)', value: metrics.impact.jobs_influenced_modeled, hint: `${metrics.impact.accepted_startups} accepted × ${metrics.assumptions.avg_jobs_per_startup} jobs/startup` },
                            { label: 'Organizations registered', value: metrics.activity.registered_organizations },
                        ]"
                        :key="i"
                        class="vl-card p-5"
                    >
                        <p class="text-sm text-slate-500">{{ item.label }}</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ item.value }}</p>
                        <p v-if="item.hint" class="mt-1 text-xs text-slate-400">{{ item.hint }}</p>
                    </div>
                </div>
            </section>

            <section v-if="metrics.testimonials?.length" class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Testimonials</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <blockquote
                        v-for="(t, i) in metrics.testimonials"
                        :key="i"
                        class="flex gap-4 rounded-lg border border-slate-100 bg-slate-50 p-5 text-sm"
                    >
                        <img
                            v-if="t.image"
                            :src="t.image"
                            :alt="t.name"
                            class="h-16 w-16 shrink-0 rounded-full border border-slate-200 object-cover object-top"
                        />
                        <div class="min-w-0">
                            <p class="text-slate-700">"{{ t.quote }}"</p>
                            <footer class="mt-3 text-slate-500">— {{ t.name }}, {{ t.role }}</footer>
                            <a v-if="t.url" :href="t.url" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-medium text-brand-600 hover:underline">Verify source</a>
                        </div>
                    </blockquote>
                </div>
            </section>

            <section class="vl-card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-900">Recent agent activity</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Read-only execution feed</p>
                </div>
                <div class="max-h-80 overflow-auto">
                    <table class="vl-data-table w-full text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr>
                                <th>Time</th>
                                <th>Agent</th>
                                <th>Decision</th>
                                <th>L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, i) in metrics.recent_agent_executions" :key="i">
                                <td class="font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                                <td class="capitalize">{{ e.agent_name }}</td>
                                <td>{{ e.decision }}</td>
                                <td>L{{ e.autonomy_level }}</td>
                            </tr>
                            <tr v-if="!metrics.recent_agent_executions.length">
                                <td colspan="4" class="py-6 text-center text-slate-500">No agent activity yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-if="archivedSnapshots.length" class="border-t border-slate-200 pt-8">
                <h2 class="text-base font-semibold text-slate-900">Archived snapshots</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Nightly JSON evidence from the repository and Google Cloud Storage
                    <span v-if="gcsBucket">(<code class="text-xs">{{ gcsBucket }}/evidence/</code>)</span>.
                </p>
                <ul class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                    <li
                        v-for="snapshot in archivedSnapshots"
                        :key="snapshot.name"
                        class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                    >
                        <div>
                            <a
                                :href="snapshot.url"
                                class="font-medium text-brand-600 hover:text-brand-700"
                                target="_blank"
                                rel="noopener"
                            >
                                {{ snapshot.name }}
                            </a>
                            <span class="ml-2 text-xs text-slate-400">{{ snapshot.date }}</span>
                        </div>
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-medium uppercase tracking-wide"
                            :class="snapshot.source === 'gcs' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ snapshot.source }}
                        </span>
                    </li>
                </ul>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
            <div class="flex justify-center">
                <Logo href="/" />
            </div>
            <p class="mt-4">Evidence auto-generated from production database</p>
        </footer>
    </div>
</template>
