<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatCard from '../../Components/Ui/StatCard.vue';

const props = defineProps({
    stats: Object,
    agents: Array,
    executions: Array,
    growth_drafts: Array,
    support_requests: Array,
    programs: Array,
});

const autonomyLabels = {
    0: 'L0 Observe',
    1: 'L1 Suggest',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};

const agentCatalog = {
    screening: {
        role: 'Core product ops',
        description: 'Scores, flags, and prioritizes every application submission.',
        schedule: 'On submit',
    },
    growth: {
        role: 'Sales & marketing',
        description: 'Drafts personalized incubator outreach and content.',
        schedule: 'Daily 09:00 UTC',
    },
    onboarding: {
        role: 'Customer onboarding',
        description: 'Proposes rubrics and program setup for new organizations.',
        schedule: 'On registration',
    },
    support: {
        role: 'Customer support',
        description: 'Answers in-app questions via RAG; escalates edge cases.',
        schedule: 'On ticket',
    },
    finance: {
        role: 'Finance ops',
        description: 'Reconciles Stripe charges and classifies revenue types.',
        schedule: 'On webhook',
    },
    success: {
        role: 'Customer success',
        description: 'Detects at-risk orgs and drafts re-engagement.',
        schedule: 'Daily',
    },
};

const filterAgent = ref('');
const filterAutonomy = ref('');
const expandedRow = ref(null);

const supportForm = useForm({
    subject: '',
    question: '',
    program_id: null,
});

const autonomyMax = computed(() => Math.max(...Object.values(props.stats.autonomy_distribution ?? {}), 1));
const agentMax = computed(() => Math.max(...Object.values(props.stats.by_agent ?? {}), 1));

const filteredExecutions = computed(() => {
    return props.executions.filter((e) => {
        if (filterAgent.value && e.agent_name !== filterAgent.value) return false;
        if (filterAutonomy.value !== '' && String(e.autonomy_level) !== filterAutonomy.value) return false;
        return true;
    });
});

const agentNames = computed(() => [...new Set(props.executions.map((e) => e.agent_name).filter(Boolean))].sort());

function barWidth(count, max) {
    return `${Math.round((count / max) * 100)}%`;
}

function capPercent(agent) {
    if (!agent.daily_action_cap) return 0;
    return Math.min(100, Math.round((agent.actions_today / agent.daily_action_cap) * 100));
}

function formatTime(iso) {
    if (!iso) return '—';
    return iso.slice(0, 19).replace('T', ' ');
}

function submitSupport() {
    supportForm.post('/ai-operations/support', {
        preserveScroll: true,
        onSuccess: () => supportForm.reset('subject', 'question'),
    });
}

function toggleRow(index) {
    expandedRow.value = expandedRow.value === index ? null : index;
}
</script>

<template>
    <AppShell
        title="AI Operations"
        subtitle="Agent activity, autonomy levels, and operational logs across screening, growth, support, finance, onboarding, and success."
    >
        <template #actions>
            <a href="/evidence-explorer/" target="_blank" class="vl-btn-secondary text-sm">Evidence explorer</a>
            <a href="/impact" target="_blank" class="vl-btn-secondary text-sm">Impact report</a>
            <a href="/api/v1/impact.json" target="_blank" class="vl-btn-ghost text-sm">JSON API</a>
        </template>

        <p v-if="stats.generated_at" class="mb-4 text-xs text-slate-400">
            Metrics refreshed {{ formatTime(stats.generated_at) }} UTC
        </p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <StatCard label="Agent actions" :value="stats.total_actions" />
            <StatCard
                label="Decisions by AI (L2–L3)"
                :value="`${stats.ai_decision_percent}%`"
                :hint="`${stats.ai_decided_actions} / ${stats.total_actions} actions`"
                variant="brand"
            />
            <StatCard label="Human hours displaced" :value="stats.human_hours_displaced" variant="success" />
            <StatCard label="Gemini API calls" :value="stats.gemini_api_calls?.toLocaleString()" />
            <StatCard label="Applications screened" :value="stats.applications_screened" />
            <Link href="/impact" class="vl-card flex flex-col justify-center p-5 transition hover:border-slate-300">
                <p class="text-sm text-slate-500">Public metrics</p>
                <p class="mt-1 text-base font-semibold text-brand-700">View impact report</p>
            </Link>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Autonomy distribution</h2>
                <p class="mt-1 text-sm text-slate-500">Target ≥30% of actions at L2–L3 for AI-native operations proof.</p>
                <div class="mt-5 space-y-4">
                    <div v-for="(count, level) in stats.autonomy_distribution" :key="level">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ autonomyLabels[level] || `L${level}` }}</span>
                            <span class="text-slate-500">{{ count }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-brand-600" :style="{ width: barWidth(count, autonomyMax) }" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Actions by agent</h2>
                <p class="mt-1 text-sm text-slate-500">Volume across all six production agents (platform-wide).</p>
                <div class="mt-5 space-y-4">
                    <div v-for="(count, agent) in stats.by_agent" :key="agent">
                        <div class="flex justify-between text-sm capitalize">
                            <span class="font-medium text-slate-700">{{ agent || 'unknown' }}</span>
                            <span class="text-slate-500">{{ count }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-slate-600" :style="{ width: barWidth(count, agentMax) }" />
                        </div>
                    </div>
                    <p v-if="!Object.keys(stats.by_agent ?? {}).length" class="text-sm text-slate-400">No agent executions yet.</p>
                </div>
            </section>
        </div>

        <section class="vl-card mt-6 p-6">
            <h2 class="text-base font-semibold text-slate-900">Agent fleet</h2>
            <p class="mt-1 text-sm text-slate-500">Six Gemini-powered agents run VentureLens operations with guardrails.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="agent in agents"
                    :key="agent.name"
                    class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold capitalize text-slate-900">{{ agent.name }}</p>
                            <p class="text-xs text-brand-600">{{ agentCatalog[agent.name]?.role }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="agent.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                        >
                            {{ agent.enabled ? 'Enabled' : 'Kill switch' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ agentCatalog[agent.name]?.description }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span>Max L{{ agent.autonomy_level }}</span>
                        <span>{{ agentCatalog[agent.name]?.schedule }}</span>
                    </div>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>Daily cap usage</span>
                            <span>{{ agent.actions_today }} / {{ agent.daily_action_cap }}</span>
                        </div>
                        <div class="mt-1 h-1.5 rounded-full bg-slate-200">
                            <div
                                class="h-1.5 rounded-full transition-all"
                                :class="capPercent(agent) >= 90 ? 'bg-amber-500' : 'bg-brand-500'"
                                :style="{ width: `${capPercent(agent)}%` }"
                            />
                        </div>
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
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Recent support tickets</p>
                    <div v-for="req in support_requests.slice(0, 3)" :key="req.id" class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm">
                        <p class="font-medium text-slate-900">
                            {{ req.subject }}
                            <span class="font-normal text-slate-400">· {{ req.status }}</span>
                        </p>
                        <p v-if="req.ai_response" class="mt-1 line-clamp-2 text-slate-600">{{ req.ai_response }}</p>
                    </div>
                </div>
            </section>

            <section class="vl-card p-6">
                <h2 class="text-base font-semibold text-slate-900">Submit support request</h2>
                <p class="mt-1 text-sm text-slate-600">Support Agent (A3) answers via RAG and logs every decision.</p>
                <form class="mt-4 space-y-3" @submit.prevent="submitSupport">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Subject</label>
                        <input
                            v-model="supportForm.subject"
                            type="text"
                            class="vl-input mt-1 w-full"
                            placeholder="Billing question, feature help…"
                            required
                        />
                        <p v-if="supportForm.errors.subject" class="mt-1 text-xs text-red-600">{{ supportForm.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600">Question</label>
                        <textarea
                            v-model="supportForm.question"
                            rows="3"
                            class="vl-input mt-1 w-full"
                            placeholder="Describe your issue…"
                            required
                        />
                        <p v-if="supportForm.errors.question" class="mt-1 text-xs text-red-600">{{ supportForm.errors.question }}</p>
                    </div>
                    <div v-if="programs.length">
                        <label class="text-xs font-medium text-slate-600">Cohort scope (optional)</label>
                        <select v-model="supportForm.program_id" class="vl-input mt-1 w-full">
                            <option :value="null">All programs</option>
                            <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <button type="submit" class="vl-btn-primary text-sm" :disabled="supportForm.processing">
                        {{ supportForm.processing ? 'Submitting…' : 'Submit to Support Agent' }}
                    </button>
                </form>
            </section>
        </div>

        <section class="vl-card mt-6 p-6">
            <h2 class="text-base font-semibold text-slate-900">Growth agent drafts</h2>
            <p class="mt-1 text-sm text-slate-600">Outreach drafts pending human review (L1 suggest).</p>
            <div v-if="growth_drafts.length" class="mt-5 grid gap-3 sm:grid-cols-2">
                <div v-for="draft in growth_drafts" :key="draft.id" class="rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm">
                    <p class="font-medium text-slate-900">{{ draft.target_organization }}</p>
                    <p class="text-slate-600">{{ draft.subject }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ draft.status }} · L{{ draft.autonomy_level }} · {{ formatTime(draft.created_at) }}</p>
                </div>
            </div>
            <p v-else class="mt-5 text-sm text-slate-400">
                No drafts yet. Scheduled daily or run
                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">php artisan agents:run-growth</code>
            </p>
        </section>

        <section class="vl-card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Execution log</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Last 50 agent decisions — click a row for details</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <select v-model="filterAgent" class="vl-input py-1.5 text-sm">
                            <option value="">All agents</option>
                            <option v-for="name in agentNames" :key="name" :value="name" class="capitalize">{{ name }}</option>
                        </select>
                        <select v-model="filterAutonomy" class="vl-input py-1.5 text-sm">
                            <option value="">All levels</option>
                            <option v-for="(label, level) in autonomyLabels" :key="level" :value="String(level)">{{ label }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="max-h-[28rem] overflow-auto">
                <table class="vl-data-table w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr>
                            <th>Time</th>
                            <th>Agent</th>
                            <th>Step</th>
                            <th>Decision</th>
                            <th>Status</th>
                            <th>L</th>
                            <th class="w-8" />
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(e, i) in filteredExecutions" :key="i">
                            <tr class="cursor-pointer hover:bg-slate-50" @click="toggleRow(i)">
                                <td class="whitespace-nowrap font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                                <td class="capitalize">{{ e.agent_name || '—' }}</td>
                                <td class="font-mono text-xs text-slate-600">{{ e.step }}</td>
                                <td>{{ e.decision }}</td>
                                <td>
                                    <span
                                        class="rounded px-1.5 py-0.5 text-xs"
                                        :class="e.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ e.status || '—' }}
                                    </span>
                                </td>
                                <td>L{{ e.autonomy_level }}</td>
                                <td class="text-slate-400">{{ expandedRow === i ? '▾' : '▸' }}</td>
                            </tr>
                            <tr v-if="expandedRow === i" class="bg-slate-50">
                                <td colspan="7" class="px-5 py-3 text-sm text-slate-600">
                                    <p v-if="e.action_taken"><span class="font-medium text-slate-700">Action:</span> {{ e.action_taken }}</p>
                                    <p v-if="e.confidence != null" class="mt-1">
                                        <span class="font-medium text-slate-700">Confidence:</span>
                                        {{ Math.round(e.confidence * 100) }}%
                                    </p>
                                    <p class="mt-1 font-mono text-xs text-slate-400">{{ formatTime(e.created_at) }}</p>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="!filteredExecutions.length">
                            <td colspan="7" class="py-8 text-center text-slate-400">No executions match the current filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="vl-card mt-6 p-6">
            <h2 class="text-base font-semibold text-slate-900">Governance & guardrails</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-sm font-medium text-slate-900">Autonomy ladder</p>
                    <p class="mt-1 text-xs text-slate-500">L0 observe → L3 autonomous. Every action is logged with its level.</p>
                </div>
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-sm font-medium text-slate-900">Daily action caps</p>
                    <p class="mt-1 text-xs text-slate-500">Per-agent rate limits prevent runaway spend and spam.</p>
                </div>
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-sm font-medium text-slate-900">Kill switch</p>
                    <p class="mt-1 text-xs text-slate-500">Disable any agent instantly via the registry <code class="text-slate-600">enabled</code> flag.</p>
                </div>
                <div class="rounded-lg border border-slate-100 p-4">
                    <p class="text-sm font-medium text-slate-900">Evidence pack</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Judges verify metrics in the
                        <a href="/evidence-explorer/" target="_blank" class="font-medium text-brand-600 hover:text-brand-700">Evidence explorer</a>
                        and nightly snapshots.
                    </p>
                </div>
            </div>
        </section>
    </AppShell>
</template>
