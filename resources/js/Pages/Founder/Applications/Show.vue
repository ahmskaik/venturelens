<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import FounderShell from '../../../Components/Layout/FounderShell.vue';
import StatusBadge from '../../../Components/Ui/StatusBadge.vue';

const props = defineProps({
    application: Object,
});

const page = usePage();
let pollInterval;

onMounted(() => {
    if (['submitted', 'processing'].includes(props.application.status)) {
        pollInterval = setInterval(() => router.reload({ only: ['application'] }), 5000);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const profileOptions = props.application.profile_options ?? {};
const sectorLabel = (key) => profileOptions.sectors?.[key] ?? key?.replace(/_/g, ' ') ?? '—';
const stageLabel = (key) => profileOptions.stages?.[key] ?? key?.replace(/_/g, ' ') ?? '—';
</script>

<template>
    <FounderShell
        :title="application.startup_name"
        :subtitle="`${application.program.name} · ${application.organization}`"
    >
        <template #actions>
            <StatusBadge :status="application.status" />
            <Link
                v-if="application.editable"
                :href="`/founder/applications/${application.id}/edit`"
                class="vl-btn-primary"
            >
                Edit profile
            </Link>
        </template>

        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <div v-if="application.status === 'needs_info'" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            The program team needs more information. Update your project profile and save to trigger re-screening.
        </div>

        <section v-if="application.screening" class="vl-card overflow-hidden">
            <div class="grid lg:grid-cols-[auto_1fr]">
                <div class="flex flex-col items-center justify-center border-b border-slate-100 bg-slate-50 px-8 py-8 lg:min-w-[200px] lg:border-b-0 lg:border-r">
                    <p class="text-xs font-medium text-slate-500">Overall score</p>
                    <div class="vl-score-display mt-3">{{ application.screening.overall_score }}</div>
                </div>
                <div class="p-6 lg:p-8">
                    <h2 class="text-lg font-semibold text-slate-900">Screening evaluation</h2>
                    <p class="mt-4 leading-relaxed text-slate-700">{{ application.screening.summary }}</p>
                    <p class="mt-3 text-sm capitalize text-slate-500">Recommendation: {{ application.screening.recommendation?.replace('_', ' ') }}</p>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div v-if="application.screening.strengths?.length">
                            <p class="text-xs font-medium text-slate-500">Strengths</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                <li v-for="(s, i) in application.screening.strengths" :key="i">✓ {{ s }}</li>
                            </ul>
                        </div>
                        <div v-if="application.screening.weaknesses?.length">
                            <p class="text-xs font-medium text-slate-500">Areas to improve</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                <li v-for="(w, i) in application.screening.weaknesses" :key="i">! {{ w }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-else-if="['submitted', 'processing'].includes(application.status)" class="vl-card border-dashed p-8 text-center">
            <p class="text-slate-600">Gemini is screening your application…</p>
            <p class="mt-2 text-sm text-slate-400">This page refreshes automatically.</p>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Project profile</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Stage</dt><dd class="font-medium capitalize">{{ stageLabel(application.stage) }}</dd></div>
                    <div><dt class="text-slate-500">Sector</dt><dd class="font-medium">{{ sectorLabel(application.sector) }}</dd></div>
                </dl>
                <div v-if="application.profile_sections?.length" class="mt-6 space-y-5 border-t border-slate-100 pt-6">
                    <div v-for="section in application.profile_sections" :key="section.key">
                        <h3 class="text-sm font-semibold text-emerald-700">{{ section.title }}</h3>
                        <dl class="mt-2 space-y-2 text-sm">
                            <div v-for="field in section.fields" :key="field.key">
                                <dt class="text-slate-500">{{ field.label }}</dt>
                                <dd class="mt-0.5 whitespace-pre-wrap text-slate-800">{{ field.value }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Program communications</h2>
                <div v-if="application.communications?.length" class="mt-4 space-y-4">
                    <div v-for="msg in application.communications" :key="msg.id" class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-900">{{ msg.subject }}</p>
                        <p class="mt-1 text-xs capitalize text-slate-400">{{ msg.decision?.replace('_', ' ') }} · {{ msg.sent_at?.slice(0, 10) }}</p>
                        <pre class="mt-3 whitespace-pre-wrap text-sm text-slate-700">{{ msg.body }}</pre>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-500">No messages from the program yet.</p>

                <div v-if="application.decision_at" class="mt-6 border-t border-slate-100 pt-4 text-sm">
                    <p class="text-slate-500">Committee decision recorded</p>
                    <p class="mt-1 font-medium capitalize">{{ application.status.replace('_', ' ') }} · {{ application.decision_at.slice(0, 10) }}</p>
                </div>
            </section>
        </div>
    </FounderShell>
</template>
