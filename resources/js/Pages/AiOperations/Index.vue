<script setup>
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatCard from '../../Components/Ui/StatCard.vue';

defineProps({
    stats: Object,
    agents: Array,
    executions: Array,
    growth_drafts: Array,
    support_requests: Array,
});

const autonomyLabels = {
    0: 'L0 Observe',
    1: 'L1 Suggest',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};
</script>

<template>
    <AppShell
        title="AI Operations"
        subtitle="Agent activity, autonomy levels, and operational logs across screening, growth, support, finance, onboarding, and success."
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Agent actions (platform)" :value="stats.total_actions" />
            <StatCard label="Decisions by AI (L2–L3)" :value="`${stats.ai_decision_percent}%`" variant="brand" />
            <StatCard label="Human hours displaced" :value="stats.human_hours_displaced" variant="success" />
            <Link href="/impact" class="vl-card flex flex-col justify-center p-5 transition hover:border-slate-300">
                <p class="text-sm text-slate-500">Public metrics</p>
                <p class="mt-1 text-base font-semibold text-brand-700">View impact report</p>
            </Link>
        </div>

        <section class="vl-card mt-6 p-6">
            <h2 class="text-base font-semibold text-slate-900">Autonomy distribution</h2>
            <div class="mt-5 space-y-4">
                <div v-for="(count, level) in stats.autonomy_distribution" :key="level">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ autonomyLabels[level] || `L${level}` }}</span>
                        <span class="text-slate-500">{{ count }}</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-100">
                        <div
                            class="h-2 rounded-full bg-brand-600"
                            :style="{ width: `${Math.round((count / Math.max(...Object.values(stats.autonomy_distribution), 1)) * 100)}%` }"
                        />
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Ask VentureLens</h2>
                <p class="mt-1 text-sm text-slate-600">
                    RAG chatbot over your applications, screening scores, and platform docs. Scope to all programs or one cohort.
                </p>
                <Link href="/ask" class="vl-btn-primary mt-4 inline-flex text-sm">
                    Open chat
                </Link>
                <div v-if="support_requests.length" class="mt-6 space-y-2">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Recent via API</p>
                    <div v-for="req in support_requests.slice(0, 3)" :key="req.id" class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm">
                        <p class="font-medium text-slate-900">{{ req.subject }}
                            <span class="font-normal text-slate-400">· {{ req.status }}</span>
                        </p>
                        <p v-if="req.ai_response" class="mt-1 line-clamp-2 text-slate-600">{{ req.ai_response }}</p>
                    </div>
                </div>
            </section>

            <section class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Growth agent</h2>
                <p class="mt-1 text-sm text-slate-600">Outreach drafts pending human review.</p>
                <div v-if="growth_drafts.length" class="mt-5 space-y-3">
                    <div v-for="draft in growth_drafts" :key="draft.id" class="rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm">
                        <p class="font-medium text-slate-900">{{ draft.target_organization }}</p>
                        <p class="text-slate-600">{{ draft.subject }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ draft.status }} · L{{ draft.autonomy_level }}</p>
                    </div>
                </div>
                <p v-else class="mt-5 text-sm text-slate-400">
                    No drafts yet. Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">php artisan agents:run-growth</code>
                </p>
            </section>
        </div>

        <section class="vl-card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Agent registry</h2>
            </div>
            <table class="vl-data-table w-full text-sm">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Enabled</th>
                        <th>Max autonomy</th>
                        <th>Daily cap</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="agent in agents" :key="agent.name">
                        <td class="capitalize font-medium text-slate-900">{{ agent.name }}</td>
                        <td>
                            <span :class="agent.enabled ? 'text-emerald-700' : 'text-slate-400'">{{ agent.enabled ? 'Yes' : 'No' }}</span>
                        </td>
                        <td>L{{ agent.autonomy_level }}</td>
                        <td>{{ agent.daily_action_cap }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="vl-card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Execution log</h2>
            </div>
            <div class="max-h-96 overflow-auto">
                <table class="vl-data-table w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr>
                            <th>Time</th>
                            <th>Agent</th>
                            <th>Step</th>
                            <th>Decision</th>
                            <th>L</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(e, i) in executions" :key="i">
                            <td class="font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                            <td class="capitalize">{{ e.agent_name || '—' }}</td>
                            <td class="font-mono text-xs text-slate-600">{{ e.step }}</td>
                            <td>{{ e.decision }}</td>
                            <td>L{{ e.autonomy_level }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppShell>
</template>
