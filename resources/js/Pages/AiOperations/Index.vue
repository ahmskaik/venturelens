<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

const props = defineProps({
    stats: Object,
    agents: Array,
    executions: Array,
    growth_drafts: Array,
    support_requests: Array,
    programs: Array,
});

const page = usePage();
const activeTab = ref('overview');
const filterAgent = ref('');
const filterAutonomy = ref('');
const selectedExecutionIndex = ref(0);

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'fleet', label: 'Agent fleet' },
    { id: 'activity', label: 'Activity log' },
    { id: 'tools', label: 'Tools' },
];

const autonomyLabels = {
    0: 'L0 Observe',
    1: 'L1 Suggest',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};

const autonomyColors = {
    0: 'bg-slate-100 text-slate-600 ring-slate-200',
    1: 'bg-sky-50 text-sky-700 ring-sky-200',
    2: 'bg-amber-50 text-amber-800 ring-amber-200',
    3: 'bg-brand-50 text-brand-800 ring-brand-200',
};

const agentCatalog = {
    screening: {
        code: 'A4',
        role: 'Core product ops',
        description: 'Scores, flags, and prioritizes every application submission.',
        schedule: 'On submit',
        accent: 'from-brand-500 to-brand-700',
        chip: 'bg-brand-100 text-brand-800',
    },
    growth: {
        code: 'A1',
        role: 'Sales & marketing',
        description: 'Drafts personalized incubator outreach and content.',
        schedule: 'Daily 09:00 UTC',
        accent: 'from-emerald-500 to-emerald-700',
        chip: 'bg-emerald-100 text-emerald-800',
    },
    onboarding: {
        code: 'A2',
        role: 'Customer onboarding',
        description: 'Proposes rubrics and program setup for new organizations.',
        schedule: 'On registration',
        accent: 'from-sky-500 to-sky-700',
        chip: 'bg-sky-100 text-sky-800',
    },
    support: {
        code: 'A3',
        role: 'Customer support',
        description: 'Answers in-app questions via RAG; escalates edge cases.',
        schedule: 'On ticket',
        accent: 'from-violet-500 to-violet-700',
        chip: 'bg-violet-100 text-violet-800',
    },
    finance: {
        code: 'A5',
        role: 'Finance ops',
        description: 'Reconciles Stripe charges and classifies revenue types.',
        schedule: 'On webhook',
        accent: 'from-amber-500 to-amber-700',
        chip: 'bg-amber-100 text-amber-800',
    },
    success: {
        code: 'A6',
        role: 'Customer success',
        description: 'Detects at-risk orgs and drafts re-engagement.',
        schedule: 'Daily',
        accent: 'from-teal-500 to-teal-700',
        chip: 'bg-teal-100 text-teal-800',
    },
};

const agentDotColors = {
    screening: 'bg-brand-500',
    growth: 'bg-emerald-500',
    onboarding: 'bg-sky-500',
    support: 'bg-violet-500',
    finance: 'bg-amber-500',
    success: 'bg-teal-500',
};

const supportForm = useForm({
    subject: '',
    question: '',
    program_id: null,
});

const autonomyMax = computed(() => Math.max(...Object.values(props.stats.autonomy_distribution ?? {}), 1));
const agentMax = computed(() => Math.max(...Object.values(props.stats.by_agent ?? {}), 1));

const l23Percent = computed(() => {
    const dist = props.stats.autonomy_distribution ?? {};
    const total = props.stats.total_actions || 1;
    const l23 = (dist[2] ?? 0) + (dist[3] ?? 0);
    return Math.round((l23 / total) * 100);
});

const filteredExecutions = computed(() => {
    return props.executions.filter((e) => {
        if (filterAgent.value && e.agent_name !== filterAgent.value) return false;
        if (filterAutonomy.value !== '' && String(e.autonomy_level) !== filterAutonomy.value) return false;
        return true;
    });
});

const selectedExecution = computed(() => {
    const list = filteredExecutions.value;
    if (!list.length) return null;
    const idx = Math.min(selectedExecutionIndex.value, list.length - 1);
    return list[idx];
});

const agentNames = computed(() => [...new Set(props.executions.map((e) => e.agent_name).filter(Boolean))].sort());

const sortedAgents = computed(() => {
    const order = ['screening', 'growth', 'onboarding', 'support', 'finance', 'success'];
    return [...props.agents].sort((a, b) => order.indexOf(a.name) - order.indexOf(b.name));
});

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

function formatStep(step) {
    if (!step) return '—';
    return step.replace(/_/g, ' ');
}

function submitSupport() {
    supportForm.post('/ai-operations/support', {
        preserveScroll: true,
        onSuccess: () => supportForm.reset('subject', 'question'),
    });
}

function selectExecution(index) {
    selectedExecutionIndex.value = index;
}
</script>

<template>
    <AppShell
        title="AI Operations"
        subtitle="Gemini agents run screening, growth, support, finance, onboarding, and success — every action logged."
    >
        <template #actions>
            <GeminiBadge />
            <a href="/evidence-explorer/" target="_blank" class="vl-btn-secondary text-sm">Evidence</a>
            <a href="/impact" target="_blank" class="vl-btn-secondary text-sm">Impact</a>
        </template>

        <div v-if="page.props.flash?.success" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <!-- Hero -->
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-950 via-brand-800 to-brand-700 px-6 py-7 text-white shadow-card-lg sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/5 blur-2xl" />
            <div class="pointer-events-none absolute -bottom-20 left-1/3 h-40 w-40 rounded-full bg-brand-400/20 blur-3xl" />

            <div class="relative flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-200">AI-native command center</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                        {{ stats.ai_decision_percent }}% of actions at L2–L3 autonomy
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-brand-100/90">
                        {{ stats.total_actions?.toLocaleString() }} agent actions logged ·
                        {{ stats.human_hours_displaced }} human hours displaced ·
                        {{ stats.gemini_api_calls?.toLocaleString() }} Gemini calls
                    </p>
                    <p v-if="stats.generated_at" class="mt-3 text-xs text-brand-200/80">
                        Refreshed {{ formatTime(stats.generated_at) }} UTC
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-brand-200">Screened</p>
                        <p class="mt-0.5 text-2xl font-semibold tabular-nums">{{ stats.applications_screened }}</p>
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-brand-200">L2–L3 share</p>
                        <p class="mt-0.5 text-2xl font-semibold tabular-nums">{{ l23Percent }}%</p>
                        <p class="text-[10px] text-brand-200">target ≥30%</p>
                    </div>
                    <Link
                        href="/ask"
                        class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-semibold text-brand-800 transition hover:bg-brand-50"
                    >
                        Ask agents
                    </Link>
                </div>
            </div>
        </section>

        <!-- Tabs -->
        <div class="vl-card mt-5 overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50/80 px-2 pt-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="shrink-0 rounded-t-lg px-4 py-2.5 text-sm font-medium transition"
                    :class="activeTab === tab.id
                        ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200 ring-b-white'
                        : 'text-slate-500 hover:text-slate-800'"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Overview -->
            <div v-show="activeTab === 'overview'" class="p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs font-medium text-slate-500">Total actions</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ stats.total_actions?.toLocaleString() }}</p>
                    </div>
                    <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-4">
                        <p class="text-xs font-medium text-brand-700">AI-decided (L2–L3)</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-brand-900">{{ stats.ai_decision_percent }}%</p>
                        <p class="mt-0.5 text-xs text-brand-600">{{ stats.ai_decided_actions }} actions</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                        <p class="text-xs font-medium text-emerald-700">Hours saved</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-900">{{ stats.human_hours_displaced }}</p>
                    </div>
                    <Link href="/impact" class="group rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-brand-300 hover:bg-brand-50/30">
                        <p class="text-xs font-medium text-slate-500">Public proof</p>
                        <p class="mt-1 text-base font-semibold text-brand-700 group-hover:text-brand-800">Impact report →</p>
                    </Link>
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="text-sm font-semibold text-slate-900">Autonomy ladder</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Distribution across L0–L3</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, level) in stats.autonomy_distribution" :key="level">
                                <div class="flex items-center justify-between text-sm">
                                    <span
                                        class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="autonomyColors[level]"
                                    >
                                        {{ autonomyLabels[level] || `L${level}` }}
                                    </span>
                                    <span class="font-medium tabular-nums text-slate-700">{{ count }}</span>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600"
                                        :style="{ width: barWidth(count, autonomyMax) }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-5">
                        <h3 class="text-sm font-semibold text-slate-900">Volume by agent</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Platform-wide execution counts</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="(count, agent) in stats.by_agent" :key="agent">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 capitalize text-slate-700">
                                        <span
                                            class="h-2 w-2 rounded-full"
                                            :class="agentDotColors[agent] ?? 'bg-slate-400'"
                                        />
                                        {{ agent || 'unknown' }}
                                        <span v-if="agentCatalog[agent]" class="font-mono text-[10px] text-slate-400">{{ agentCatalog[agent].code }}</span>
                                    </span>
                                    <span class="font-medium tabular-nums text-slate-600">{{ count }}</span>
                                </div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-slate-500" :style="{ width: barWidth(count, agentMax) }" />
                                </div>
                            </div>
                            <p v-if="!Object.keys(stats.by_agent ?? {}).length" class="text-sm text-slate-400">No executions yet.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in [
                        { title: 'Autonomy ladder', text: 'L0 observe → L3 autonomous. Every action logs its level.' },
                        { title: 'Daily caps', text: 'Per-agent rate limits prevent runaway spend.' },
                        { title: 'Kill switch', text: 'Disable any agent via registry enabled flag.' },
                        { title: 'Evidence pack', text: 'Judges verify in Evidence explorer + nightly snapshots.' },
                    ]" :key="item.title" class="rounded-lg border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <p class="text-xs font-semibold text-slate-800">{{ item.title }}</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ item.text }}</p>
                    </div>
                </div>
            </div>

            <!-- Fleet -->
            <div v-show="activeTab === 'fleet'" class="p-5 sm:p-6">
                <p class="mb-5 text-sm text-slate-500">Six Gemini-powered agents with guardrails, caps, and kill switches.</p>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="agent in sortedAgents"
                        :key="agent.name"
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-card"
                    >
                        <div class="h-1.5 bg-gradient-to-r" :class="agentCatalog[agent.name]?.accent ?? 'from-slate-400 to-slate-600'" />
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold capitalize text-slate-900">{{ agent.name }}</h3>
                                        <span
                                            v-if="agentCatalog[agent.name]"
                                            class="rounded px-1.5 py-0.5 font-mono text-[10px] font-semibold"
                                            :class="agentCatalog[agent.name].chip"
                                        >
                                            {{ agentCatalog[agent.name].code }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ agentCatalog[agent.name]?.role }}</p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="agent.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                                >
                                    {{ agent.enabled ? 'Live' : 'Off' }}
                                </span>
                            </div>

                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ agentCatalog[agent.name]?.description }}</p>

                            <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                                <span class="rounded-md bg-slate-100 px-2 py-1 font-medium text-slate-600">Max L{{ agent.autonomy_level }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-500">{{ agentCatalog[agent.name]?.schedule }}</span>
                            </div>

                            <div class="mt-4">
                                <div class="flex justify-between text-[11px] text-slate-500">
                                    <span>Daily cap</span>
                                    <span class="tabular-nums">{{ agent.actions_today }} / {{ agent.daily_action_cap }}</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="capPercent(agent) >= 90 ? 'bg-amber-500' : 'bg-brand-500'"
                                        :style="{ width: `${capPercent(agent)}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Activity log -->
            <div v-show="activeTab === 'activity'" class="p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <p class="text-sm text-slate-500">Last 50 decisions · select a row for detail</p>
                    <div class="flex flex-wrap gap-2">
                        <select v-model="filterAgent" class="vl-input py-1.5 text-sm" @change="selectedExecutionIndex = 0">
                            <option value="">All agents</option>
                            <option v-for="name in agentNames" :key="name" :value="name" class="capitalize">{{ name }}</option>
                        </select>
                        <select v-model="filterAutonomy" class="vl-input py-1.5 text-sm" @change="selectedExecutionIndex = 0">
                            <option value="">All levels</option>
                            <option v-for="(label, level) in autonomyLabels" :key="level" :value="String(level)">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[1fr_280px]">
                    <div class="max-h-[32rem] overflow-auto border-r border-slate-100">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50 text-left text-xs font-medium text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5">Time</th>
                                    <th class="px-4 py-2.5">Agent</th>
                                    <th class="px-4 py-2.5">Step</th>
                                    <th class="hidden px-4 py-2.5 sm:table-cell">Decision</th>
                                    <th class="px-4 py-2.5">L</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="(e, i) in filteredExecutions"
                                    :key="i"
                                    class="cursor-pointer transition"
                                    :class="selectedExecutionIndex === i ? 'bg-brand-50' : 'hover:bg-slate-50'"
                                    @click="selectExecution(i)"
                                >
                                    <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-500">{{ e.created_at?.slice(11, 19) }}</td>
                                    <td class="px-4 py-2.5 capitalize text-slate-800">{{ e.agent_name || '—' }}</td>
                                    <td class="max-w-[8rem] truncate px-4 py-2.5 text-xs text-slate-600" :title="e.step">{{ formatStep(e.step) }}</td>
                                    <td class="hidden max-w-[10rem] truncate px-4 py-2.5 text-slate-700 sm:table-cell" :title="e.decision">{{ e.decision }}</td>
                                    <td class="px-4 py-2.5">
                                        <span
                                            class="inline-flex rounded px-1.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                                            :class="autonomyColors[e.autonomy_level] ?? autonomyColors[0]"
                                        >
                                            L{{ e.autonomy_level }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!filteredExecutions.length">
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-400">No executions match filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <aside class="hidden bg-slate-50/50 p-4 lg:block">
                        <template v-if="selectedExecution">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Selected action</p>
                            <p class="mt-2 font-semibold capitalize text-slate-900">{{ selectedExecution.agent_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ formatTime(selectedExecution.created_at) }}</p>

                            <dl class="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-400">Step</dt>
                                    <dd class="font-mono text-slate-700">{{ selectedExecution.step }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Decision</dt>
                                    <dd class="text-slate-800">{{ selectedExecution.decision }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-400">Status</dt>
                                    <dd>
                                        <span
                                            class="rounded px-1.5 py-0.5 text-xs"
                                            :class="selectedExecution.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'"
                                        >
                                            {{ selectedExecution.status || '—' }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="selectedExecution.confidence != null">
                                    <dt class="text-xs text-slate-400">Confidence</dt>
                                    <dd class="font-semibold tabular-nums text-slate-800">{{ Math.round(selectedExecution.confidence * 100) }}%</dd>
                                </div>
                            </dl>

                            <p v-if="selectedExecution.action_taken" class="mt-4 rounded-lg border border-slate-200 bg-white p-3 text-xs leading-relaxed text-slate-600">
                                {{ selectedExecution.action_taken }}
                            </p>
                        </template>
                        <p v-else class="text-sm text-slate-400">Select an execution to inspect.</p>
                    </aside>
                </div>

                <!-- Mobile detail -->
                <div v-if="selectedExecution" class="border-t border-slate-100 bg-slate-50 p-4 lg:hidden">
                    <p class="text-xs font-semibold text-slate-500">Action detail</p>
                    <p class="mt-1 text-sm text-slate-700">{{ selectedExecution.action_taken }}</p>
                </div>
            </div>

            <!-- Tools -->
            <div v-show="activeTab === 'tools'" class="p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="rounded-xl border border-brand-200 bg-gradient-to-br from-brand-50 to-white p-5">
                        <h3 class="font-semibold text-slate-900">Ask VentureLens</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            RAG chat over applications, screening scores, and platform docs.
                        </p>
                        <Link href="/ask" class="vl-btn-primary mt-4 inline-flex text-sm">Open chat</Link>

                        <div v-if="support_requests.length" class="mt-6 border-t border-brand-100 pt-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Recent tickets</p>
                            <ul class="mt-3 space-y-2">
                                <li
                                    v-for="req in support_requests.slice(0, 4)"
                                    :key="req.id"
                                    class="rounded-lg border border-slate-100 bg-white px-3 py-2 text-sm"
                                >
                                    <p class="font-medium text-slate-900">{{ req.subject }}</p>
                                    <p class="text-xs capitalize text-slate-400">{{ req.status }}</p>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 p-5">
                        <h3 class="font-semibold text-slate-900">Support Agent (A3)</h3>
                        <p class="mt-1 text-sm text-slate-500">Submit a question — answered via RAG with full audit log.</p>
                        <form class="mt-4 space-y-3" @submit.prevent="submitSupport">
                            <input
                                v-model="supportForm.subject"
                                type="text"
                                class="vl-input w-full"
                                placeholder="Subject"
                                required
                            />
                            <textarea
                                v-model="supportForm.question"
                                rows="3"
                                class="vl-input w-full"
                                placeholder="Your question…"
                                required
                            />
                            <select v-if="programs.length" v-model="supportForm.program_id" class="vl-input w-full">
                                <option :value="null">All programs</option>
                                <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <button type="submit" class="vl-btn-primary w-full text-sm" :disabled="supportForm.processing">
                                {{ supportForm.processing ? 'Submitting…' : 'Submit to Support Agent' }}
                            </button>
                        </form>
                    </section>
                </div>

                <section class="mt-5 rounded-xl border border-slate-200 p-5">
                    <h3 class="font-semibold text-slate-900">Growth agent drafts</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Outreach pending human review (L1 suggest)</p>
                    <div v-if="growth_drafts.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="draft in growth_drafts"
                            :key="draft.id"
                            class="rounded-lg border border-slate-100 bg-slate-50 p-4"
                        >
                            <p class="font-medium text-slate-900">{{ draft.target_organization }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ draft.subject }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ draft.status }} · L{{ draft.autonomy_level }} · {{ formatTime(draft.created_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-400">
                        No drafts yet — run <code class="rounded bg-slate-100 px-1 text-xs">php artisan agents:run-growth</code>
                    </p>
                </section>
            </div>
        </div>
    </AppShell>
</template>
