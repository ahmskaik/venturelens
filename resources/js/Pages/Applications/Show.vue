<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';
import AgentTraceTimeline from '../../Components/Ui/AgentTraceTimeline.vue';

const props = defineProps({
    application: Object,
    program: Object,
    rubric: Object,
    founder_communication: Object,
    decisions: Array,
});

const page = usePage();
const activeTab = ref('summary');
const selectedCriterionName = ref(null);

const tabs = [
    { id: 'summary', label: 'Summary' },
    { id: 'rubric', label: 'Rubric & scores' },
    { id: 'application', label: 'Application' },
    { id: 'trace', label: 'Agent trace' },
];

const sectorLabel = (key) => {
    if (!key) return '—';
    return props.application.profile_options?.sectors?.[key] ?? key.replace(/_/g, ' ');
};

const stageLabel = (key) => {
    if (!key) return '—';
    return props.application.profile_options?.stages?.[key] ?? key.replace(/_/g, ' ');
};

const decisionStyles = {
    accepted: 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100',
    rejected: 'border-red-300 bg-red-50 text-red-800 hover:bg-red-100',
    shortlisted: 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100',
    needs_info: 'border-orange-300 bg-orange-50 text-orange-800 hover:bg-orange-100',
};

const screeningInProgress = computed(() => props.application?.status === 'processing');

const criterionBreakdown = computed(() => {
    const scores = props.application.screening?.criterion_scores ?? [];
    const rubricCriteria = props.rubric?.criteria ?? [];

    const findScore = (name) => scores.find(
        (item) => item.name?.toLowerCase() === name?.toLowerCase(),
    );

    if (rubricCriteria.length) {
        return rubricCriteria.map((criterion) => {
            const match = findScore(criterion.name);

            return {
                name: criterion.name,
                weight: criterion.weight,
                description: criterion.description,
                scoring_guide: criterion.scoring_guide,
                score: match?.score ?? null,
                reasoning: match?.reasoning ?? null,
            };
        });
    }

    return scores.map((item) => ({
        name: item.name,
        weight: null,
        description: null,
        scoring_guide: null,
        score: item.score ?? null,
        reasoning: item.reasoning ?? null,
    }));
});

const selectedCriterion = computed(() => {
    const list = criterionBreakdown.value;
    if (!list.length) return null;

    return list.find((item) => item.name === selectedCriterionName.value) ?? list[0];
});

watch(activeTab, (tab) => {
    if (tab === 'rubric' && criterionBreakdown.value.length && !selectedCriterionName.value) {
        selectedCriterionName.value = criterionBreakdown.value[0].name;
    }
});

function formatScore(score) {
    if (score === null || score === undefined || score === '') return '—';
    return `${Number(score)}/100`;
}

function weightedContribution(score, weight) {
    if (score === null || score === undefined || !weight) return null;
    return ((Number(score) * Number(weight)) / 100).toFixed(1);
}

function scoreBarWidth(score) {
    if (score === null || score === undefined) return 0;
    return Math.max(0, Math.min(100, Number(score)));
}

function scoreBarClass(score) {
    const tone = scoreTone(score);
    return {
        emerald: 'bg-emerald-500',
        amber: 'bg-amber-500',
        red: 'bg-red-500',
        slate: 'bg-slate-300',
    }[tone];
}

function formatScoringGuide(guide) {
    if (!guide) return null;
    if (/^1=/.test(guide) && /10=/.test(guide)) {
        return guide.replace(/^1=/, '0=').replace(/, 10=/, ', 100=');
    }
    return guide;
}

function scoreTone(score) {
    if (score === null || score === undefined) return 'slate';
    if (score >= 70) return 'emerald';
    if (score >= 40) return 'amber';
    return 'red';
}

function scoreChipClass(score) {
    const tone = scoreTone(score);
    return {
        emerald: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        amber: 'bg-amber-50 text-amber-800 ring-amber-200',
        red: 'bg-red-50 text-red-800 ring-red-200',
        slate: 'bg-slate-50 text-slate-600 ring-slate-200',
    }[tone];
}

function rescreen() {
    router.post(`/applications/${props.application.id}/rescreen`);
}

function decide(decision) {
    router.post(`/applications/${props.application.id}/decision`, { decision });
}

function sendEmail() {
    if (!props.founder_communication?.id) return;
    router.post(
        `/applications/${props.application.id}/communications/${props.founder_communication.id}/send`,
    );
}

function openRubricTab(criterionName = null) {
    activeTab.value = 'rubric';
    if (criterionName) {
        selectedCriterionName.value = criterionName;
    } else if (!selectedCriterionName.value && criterionBreakdown.value.length) {
        selectedCriterionName.value = criterionBreakdown.value[0].name;
    }
}
</script>

<template>
    <AppShell
        :title="application.startup_name"
        :subtitle="`${application.founder_name} · ${application.founder_email}`"
    >
        <template #actions>
            <div class="flex flex-wrap items-center gap-3">
                <StatusBadge :status="application.status" />
                <button
                    class="vl-btn-primary disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="screeningInProgress"
                    @click="rescreen"
                >
                    <span v-if="screeningInProgress" class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        Running…
                    </span>
                    <span v-else>Replay screening</span>
                </button>
            </div>
        </template>

        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <!-- Screening hero -->
        <section v-if="application.screening" class="vl-card p-6">
            <div class="flex flex-wrap items-start gap-6">
                <div class="flex h-20 w-20 shrink-0 flex-col items-center justify-center rounded-full border-4 border-brand-100 bg-brand-50 text-brand-700">
                    <span class="text-xl font-semibold tabular-nums leading-none">{{ application.screening.overall_score }}</span>
                    <span class="mt-0.5 text-[10px] font-medium text-brand-500">/ 100</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold capitalize text-brand-800">
                            {{ application.screening.recommendation }}
                        </span>
                        <span v-if="rubric?.name" class="text-xs text-slate-400">{{ rubric.name }}</span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ application.screening.summary }}</p>
                    <p class="mt-2 text-xs text-slate-400">
                        {{ application.screening.model }} · {{ application.screening.latency_ms }}ms ·
                        {{ application.screening.prompt_tokens + application.screening.completion_tokens }} tokens
                    </p>
                    <div
                        v-if="application.screening.completeness === 'incomplete'"
                        class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
                    >
                        Incomplete application
                        <span v-if="application.screening.missing_fields?.length">
                            — missing {{ application.screening.missing_fields.join(', ') }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="criterionBreakdown.length" class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-5">
                <button
                    v-for="criterion in criterionBreakdown"
                    :key="criterion.name"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium ring-1 ring-inset transition hover:ring-brand-300"
                    :class="scoreChipClass(criterion.score)"
                    @click="openRubricTab(criterion.name)"
                >
                    {{ criterion.name }}
                    <span class="font-semibold tabular-nums">{{ formatScore(criterion.score) }}</span>
                </button>
                <button type="button" class="vl-btn-ghost text-xs text-brand-600" @click="openRubricTab">
                    View justification →
                </button>
            </div>
        </section>

        <section v-else-if="screeningInProgress" class="vl-card px-6 py-10 text-center">
            <svg class="mx-auto h-8 w-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
            </svg>
            <p class="mt-4 font-medium text-slate-800">Screening in progress</p>
            <p class="mt-1 text-sm text-slate-500">Typically completes in 20–40 seconds.</p>
        </section>

        <section v-else class="vl-card border-dashed p-8 text-center">
            <p class="text-slate-500">Screening pending or failed.</p>
            <button class="vl-btn-primary mt-4" @click="rescreen">Run screening</button>
        </section>

        <!-- Committee decision -->
        <section class="vl-card mt-4 p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Committee decision</h2>
                    <p class="text-xs text-slate-500">AI score {{ application.ai_overall_score ?? '—' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="d in decisions"
                        :key="d"
                        class="rounded-lg border px-4 py-2 text-sm font-semibold capitalize transition"
                        :class="decisionStyles[d] ?? 'border-slate-300 bg-white hover:bg-slate-50'"
                        @click="decide(d)"
                    >
                        {{ d.replace('_', ' ') }}
                    </button>
                </div>
            </div>
            <p v-if="application.decision_at" class="mt-3 text-xs text-slate-400">
                Last decision {{ application.decision_at.slice(0, 16).replace('T', ' ') }}
            </p>
        </section>

        <!-- Founder email -->
        <section v-if="founder_communication" class="vl-card mt-4 border-emerald-200 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-emerald-900">Founder email draft</h2>
                    <p class="text-xs capitalize text-slate-500">{{ founder_communication.decision }} · {{ founder_communication.status }}</p>
                </div>
                <button
                    v-if="founder_communication.status === 'draft'"
                    class="vl-btn-primary text-sm"
                    @click="sendEmail"
                >
                    Approve & send
                </button>
            </div>
            <p class="mt-3 text-sm font-semibold text-slate-900">{{ founder_communication.subject }}</p>
            <p class="mt-2 line-clamp-4 whitespace-pre-wrap text-sm text-slate-600">{{ founder_communication.body }}</p>
        </section>

        <!-- Tabbed detail -->
        <div class="vl-card mt-4 overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-200 px-2 pt-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="shrink-0 rounded-t-lg px-4 py-2.5 text-sm font-medium transition"
                    :class="activeTab === tab.id
                        ? 'bg-white text-brand-700 ring-1 ring-slate-200 ring-b-white'
                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="p-5">
                <!-- Summary -->
                <div v-show="activeTab === 'summary'">
                    <div v-if="!application.screening" class="text-sm text-slate-500">
                        Run screening to see strengths, weaknesses, and risk flags.
                    </div>
                    <div v-else class="grid gap-5 md:grid-cols-3">
                        <div v-if="application.screening.strengths?.length">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Strengths</p>
                            <ul class="mt-2 space-y-2 text-sm text-slate-700">
                                <li v-for="(s, i) in application.screening.strengths" :key="i" class="flex gap-2">
                                    <span class="text-emerald-500">+</span><span>{{ s }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="application.screening.weaknesses?.length">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Weaknesses</p>
                            <ul class="mt-2 space-y-2 text-sm text-slate-700">
                                <li v-for="(w, i) in application.screening.weaknesses" :key="i" class="flex gap-2">
                                    <span class="text-amber-500">−</span><span>{{ w }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="application.screening.risk_flags?.length">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Risk flags</p>
                            <ul class="mt-2 space-y-2 text-sm">
                                <li
                                    v-for="(r, i) in application.screening.risk_flags"
                                    :key="i"
                                    class="rounded-md bg-red-50 px-2 py-1.5 text-red-800"
                                >
                                    <span class="font-medium capitalize">{{ r.severity }}</span> — {{ r.message }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p
                        v-if="application.screening && !application.screening.strengths?.length && !application.screening.weaknesses?.length && !application.screening.risk_flags?.length"
                        class="text-sm text-slate-500"
                    >
                        No structured summary fields — check the Rubric & scores tab.
                    </p>
                </div>

                <!-- Rubric -->
                <div v-show="activeTab === 'rubric'">
                    <div v-if="criterionBreakdown.length && selectedCriterion" class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ rubric?.name ?? 'Screening rubric' }}</p>
                                <p class="text-xs text-slate-500">Each criterion scored 0–100, weighted by rubric %</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
                            <!-- Criterion nav -->
                            <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                                <button
                                    v-for="criterion in criterionBreakdown"
                                    :key="criterion.name"
                                    type="button"
                                    class="min-w-[180px] shrink-0 rounded-xl border px-3 py-3 text-left transition lg:min-w-0 lg:w-full"
                                    :class="selectedCriterion.name === criterion.name
                                        ? 'border-brand-300 bg-brand-50 ring-1 ring-brand-200'
                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                    @click="selectedCriterionName = criterion.name"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-sm font-medium text-slate-900">{{ criterion.name }}</span>
                                        <span
                                            class="shrink-0 text-xs font-semibold tabular-nums"
                                            :class="{
                                                'text-emerald-700': scoreTone(criterion.score) === 'emerald',
                                                'text-amber-700': scoreTone(criterion.score) === 'amber',
                                                'text-red-700': scoreTone(criterion.score) === 'red',
                                                'text-slate-500': scoreTone(criterion.score) === 'slate',
                                            }"
                                        >
                                            {{ formatScore(criterion.score) }}
                                        </span>
                                    </div>
                                    <div v-if="criterion.weight" class="mt-1 text-[11px] text-slate-400">{{ criterion.weight }}% weight</div>
                                    <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full"
                                            :class="scoreBarClass(criterion.score)"
                                            :style="{ width: `${scoreBarWidth(criterion.score)}%` }"
                                        />
                                    </div>
                                </button>
                            </div>

                            <!-- Criterion detail -->
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">{{ selectedCriterion.name }}</h3>
                                        <p v-if="selectedCriterion.description" class="mt-1 text-sm text-slate-500">
                                            {{ selectedCriterion.description }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-3xl font-semibold tabular-nums text-slate-900">
                                            {{ formatScore(selectedCriterion.score) }}
                                        </p>
                                        <p
                                            v-if="weightedContribution(selectedCriterion.score, selectedCriterion.weight)"
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            ~{{ weightedContribution(selectedCriterion.score, selectedCriterion.weight) }} pts toward overall
                                            <span v-if="selectedCriterion.weight">({{ selectedCriterion.weight }}% weight)</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="scoreBarClass(selectedCriterion.score)"
                                        :style="{ width: `${scoreBarWidth(selectedCriterion.score)}%` }"
                                    />
                                </div>

                                <div class="mt-5 rounded-lg border border-slate-200 bg-white p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">AI justification</p>
                                    <p v-if="selectedCriterion.reasoning" class="mt-2 text-sm leading-relaxed text-slate-700">
                                        {{ selectedCriterion.reasoning }}
                                    </p>
                                    <p v-else class="mt-2 text-sm italic text-slate-400">
                                        No per-criterion reasoning recorded — replay screening to regenerate.
                                    </p>
                                </div>

                                <p v-if="selectedCriterion.scoring_guide" class="mt-4 text-xs leading-relaxed text-slate-400">
                                    <span class="font-medium text-slate-500">Rubric scale (0–100):</span>
                                    {{ formatScoringGuide(selectedCriterion.scoring_guide) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No rubric scores yet.</p>
                </div>

                <!-- Application -->
                <div v-show="activeTab === 'application'">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-slate-500">Stage</dt><dd class="font-medium">{{ stageLabel(application.stage) }}</dd></div>
                        <div><dt class="text-slate-500">Sector</dt><dd class="font-medium">{{ sectorLabel(application.sector) }}</dd></div>
                        <div><dt class="text-slate-500">Country</dt><dd class="font-medium">{{ application.country_code }}</dd></div>
                        <div><dt class="text-slate-500">Submitted</dt><dd class="font-medium">{{ application.submitted_at?.slice(0, 10) ?? '—' }}</dd></div>
                    </dl>

                    <div v-if="application.files?.length" class="mt-5 border-t border-slate-100 pt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Attachments</p>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            <li v-for="file in application.files" :key="file.id">
                                <span class="capitalize">{{ file.type.replace('_', ' ') }}</span> · {{ file.original_filename }}
                            </li>
                        </ul>
                    </div>

                    <div v-if="application.profile_sections?.length" class="mt-5 space-y-5 border-t border-slate-100 pt-5">
                        <div v-for="section in application.profile_sections" :key="section.key">
                            <h3 class="text-sm font-semibold text-slate-900">{{ section.title }}</h3>
                            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div v-for="field in section.fields" :key="field.key">
                                    <dt class="text-slate-500">{{ field.label }}</dt>
                                    <dd class="mt-0.5 text-slate-800">
                                        <a
                                            v-if="field.type === 'url'"
                                            :href="field.value"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-brand-600 hover:underline"
                                        >{{ field.value }}</a>
                                        <span v-else-if="field.type === 'textarea'" class="block whitespace-pre-wrap">{{ field.value }}</span>
                                        <span v-else>{{ field.value }}</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Trace -->
                <div v-show="activeTab === 'trace'">
                    <AgentTraceTimeline compact :executions="application.agent_executions" />
                </div>
            </div>
        </div>

        <p class="mt-6 text-center">
            <Link :href="`/programs/${program.id}/applications`" class="vl-btn-ghost text-brand-600">← Back to applications</Link>
        </p>
    </AppShell>
</template>
