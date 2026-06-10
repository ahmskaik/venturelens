<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';
import AgentTraceTimeline from '../../Components/Ui/AgentTraceTimeline.vue';

const props = defineProps({
    application: Object,
    program: Object,
    founder_communication: Object,
    decisions: Array,
});

const page = usePage();

const decisionStyles = {
    accepted: 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100',
    rejected: 'border-red-300 bg-red-50 text-red-800 hover:bg-red-100',
    shortlisted: 'border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100',
    needs_info: 'border-orange-300 bg-orange-50 text-orange-800 hover:bg-orange-100',
};

function rescreen() {
    router.post(`/applications/${props.application.id}/rescreen`);
}

function decide(decision) {
    router.post(`/applications/${props.application.id}/decision`, { decision });
}

function sendEmail() {
    if (!props.founder_communication?.id) return;
    router.post(
        `/applications/${props.application.id}/communications/${props.founder_communication.id}/send`
    );
}
</script>

<template>
    <AppShell
        :title="application.startup_name"
        :subtitle="`${application.founder_name} · ${application.founder_email}`"
        badge="Application review"
    >
        <template #actions>
            <div class="flex flex-wrap items-center gap-3">
                <StatusBadge :status="application.status" />
                <button class="vl-btn-primary" @click="rescreen">Replay screening</button>
            </div>
        </template>

        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <!-- Gemini hero panel — primary video shot -->
        <section v-if="application.screening" class="vl-card-elevated overflow-hidden">
            <div class="grid lg:grid-cols-[auto_1fr]">
                <div class="flex flex-col items-center justify-center bg-gradient-to-br from-brand-600 to-violet-800 px-10 py-10 text-white lg:min-w-[220px]">
                    <p class="text-xs font-semibold uppercase tracking-widest text-violet-200">Gemini score</p>
                    <div class="vl-score-ring mt-4">{{ application.screening.overall_score }}</div>
                    <GeminiBadge size="lg" class="mt-6 !border-white/30 !from-white/10 !to-white/5 !text-white" />
                </div>
                <div class="p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="vl-display text-xl font-bold text-slate-900">Gemini evaluation</h2>
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-medium capitalize text-brand-800">
                            {{ application.screening.recommendation ?? application.recommendation }}
                        </span>
                    </div>
                    <p class="mt-4 leading-relaxed text-slate-700">{{ application.screening.summary }}</p>
                    <p class="mt-4 text-xs text-slate-400">
                        {{ application.screening.model }} · {{ application.screening.latency_ms }}ms ·
                        {{ application.screening.prompt_tokens + application.screening.completion_tokens }} tokens
                    </p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div v-if="application.screening.strengths?.length">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Strengths</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                <li v-for="(s, i) in application.screening.strengths" :key="i" class="flex gap-2">
                                    <span class="text-emerald-500">✓</span>{{ s }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="application.screening.weaknesses?.length">
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Weaknesses</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                <li v-for="(w, i) in application.screening.weaknesses" :key="i" class="flex gap-2">
                                    <span class="text-amber-500">!</span>{{ w }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="application.screening.risk_flags?.length">
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-700">Risk flags</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                <li v-for="(r, i) in application.screening.risk_flags" :key="i" class="rounded-lg bg-red-50 px-2 py-1 text-red-800">
                                    [{{ r.severity }}] {{ r.message }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-else class="vl-card border-dashed p-10 text-center">
            <p class="text-slate-500">Screening pending or failed.</p>
            <button class="vl-btn-primary mt-4" @click="rescreen">Run Gemini screening</button>
        </section>

        <!-- Committee decision -->
        <section class="vl-card mt-6 p-6">
            <h2 class="vl-display text-lg font-bold">Committee decision</h2>
            <p class="mt-1 text-sm text-slate-600">
                AI score: {{ application.ai_overall_score ?? '—' }} · Human L2 with AI-prepared context
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <button
                    v-for="d in decisions"
                    :key="d"
                    class="rounded-xl border-2 px-5 py-2.5 text-sm font-semibold capitalize transition"
                    :class="decisionStyles[d] ?? 'border-slate-300 bg-white hover:bg-slate-50'"
                    @click="decide(d)"
                >
                    {{ d.replace('_', ' ') }}
                </button>
            </div>
            <p v-if="application.decision_at" class="mt-4 text-xs text-slate-400">
                Last decision: {{ application.decision_at.slice(0, 16).replace('T', ' ') }}
            </p>
        </section>

        <!-- Founder email -->
        <section v-if="founder_communication" class="vl-card mt-6 border-emerald-200 p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="vl-display text-lg font-bold text-emerald-900">Founder email</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Gemini draft · <span class="capitalize">{{ founder_communication.decision }}</span> · {{ founder_communication.status }}
                    </p>
                </div>
                <button
                    v-if="founder_communication.status === 'draft'"
                    class="vl-btn-primary bg-emerald-600 hover:bg-emerald-700"
                    @click="sendEmail"
                >
                    Approve & send
                </button>
            </div>
            <p class="mt-5 font-semibold text-slate-900">{{ founder_communication.subject }}</p>
            <pre class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-50 p-5 text-sm leading-relaxed text-slate-700">{{ founder_communication.body }}</pre>
            <p v-if="founder_communication.sent_at" class="mt-3 text-xs text-emerald-700">
                Sent {{ founder_communication.sent_at.slice(0, 16).replace('T', ' ') }}
            </p>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Application details -->
            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Application details</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Stage</dt><dd class="font-medium">{{ application.stage }}</dd></div>
                    <div><dt class="text-slate-500">Sector</dt><dd class="font-medium">{{ application.sector || '—' }}</dd></div>
                </dl>
                <div v-if="application.form_data" class="mt-6 space-y-4 border-t border-slate-100 pt-6 text-sm">
                    <div v-for="(value, key) in application.form_data" :key="key">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ String(key).replace('_', ' ') }}</p>
                        <p class="mt-1 text-slate-800">{{ value }}</p>
                    </div>
                </div>
            </section>

            <!-- Agent trace timeline -->
            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Agent execution trace</h2>
                <p class="mt-1 text-sm text-slate-500">Every step logged for audit and judges</p>
                <AgentTraceTimeline :executions="application.agent_executions" />
            </section>
        </div>

        <p class="mt-8 text-center">
            <Link :href="`/programs/${program.id}/applications`" class="vl-btn-ghost text-brand-600">← Back to applications</Link>
        </p>
    </AppShell>
</template>
