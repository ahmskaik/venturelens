<script setup>
import { Link } from '@inertiajs/vue3';
import FounderShell from '../../../Components/Layout/FounderShell.vue';
import StatCard from '../../../Components/Ui/StatCard.vue';
import StatusBadge from '../../../Components/Ui/StatusBadge.vue';

defineProps({
    stats: Object,
    applications: Array,
    open_programs: Array,
});
</script>

<template>
    <FounderShell
        title="Founder dashboard"
        subtitle="Track applications, AI evaluation, and program updates."
        badge="Your ventures"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Applications" :value="stats.total_applications" variant="brand" />
            <StatCard label="In review" :value="stats.in_review" />
            <StatCard label="Decided" :value="stats.decided" variant="success" />
            <StatCard label="Needs your action" :value="stats.needs_action" />
        </div>

        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="vl-display text-lg font-bold">Recent applications</h2>
                <Link href="/founder/applications" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">View all →</Link>
            </div>
            <div class="mt-4 space-y-3">
                <Link
                    v-for="app in applications"
                    :key="app.id"
                    :href="`/founder/applications/${app.id}`"
                    class="vl-card flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:border-emerald-200"
                >
                    <div>
                        <p class="font-semibold text-slate-900">{{ app.startup_name }}</p>
                        <p class="text-sm text-slate-500">{{ app.program.name }} · {{ app.organization }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-if="app.ai_overall_score" class="text-lg font-bold text-brand-600">{{ app.ai_overall_score }}</span>
                        <StatusBadge :status="app.status" />
                    </div>
                </Link>
                <p v-if="!applications.length" class="vl-card px-5 py-8 text-center text-slate-500">
                    No applications yet.
                    <Link href="/founder/programs" class="font-medium text-emerald-600">Browse open programs</Link>
                </p>
            </div>
        </section>

        <section class="mt-10">
            <h2 class="vl-display text-lg font-bold">Open programs</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div v-for="program in open_programs" :key="program.id" class="vl-card p-5">
                    <p class="font-semibold text-slate-900">{{ program.name }}</p>
                    <p class="text-sm text-slate-500">{{ program.organization }}</p>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ program.description }}</p>
                    <Link :href="`/apply/${program.slug}`" class="vl-btn-primary mt-4 inline-flex text-sm">Apply now →</Link>
                </div>
            </div>
        </section>
    </FounderShell>
</template>
