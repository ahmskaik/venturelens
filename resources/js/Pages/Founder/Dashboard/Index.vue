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
        title="Dashboard"
        subtitle="Track applications, screening results, and program updates."
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Applications" :value="stats.total_applications" variant="brand" />
            <StatCard label="In review" :value="stats.in_review" />
            <StatCard label="Decided" :value="stats.decided" variant="success" />
            <StatCard label="Needs your action" :value="stats.needs_action" />
        </div>

        <section class="mt-8">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Recent applications</h2>
                <Link href="/founder/applications" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</Link>
            </div>
            <div class="mt-4 space-y-2">
                <Link
                    v-for="app in applications"
                    :key="app.id"
                    :href="`/founder/applications/${app.id}`"
                    class="vl-card flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:border-slate-300"
                >
                    <div>
                        <p class="font-medium text-slate-900">{{ app.startup_name }}</p>
                        <p class="text-sm text-slate-500">{{ app.program.name }} · {{ app.organization }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-if="app.ai_overall_score" class="text-lg font-semibold tabular-nums text-brand-700">{{ app.ai_overall_score }}</span>
                        <StatusBadge :status="app.status" />
                    </div>
                </Link>
                <p v-if="!applications.length" class="vl-card px-5 py-8 text-center text-slate-500">
                    No applications yet.
                    <Link href="/founder/programs" class="font-medium text-brand-600 hover:underline">Browse open programs</Link>
                </p>
            </div>
        </section>

        <section class="mt-8">
            <h2 class="text-base font-semibold text-slate-900">Open programs</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div v-for="program in open_programs" :key="program.id" class="vl-card p-5">
                    <p class="font-medium text-slate-900">{{ program.name }}</p>
                    <p class="text-sm text-slate-500">{{ program.organization }}</p>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ program.description }}</p>
                    <Link :href="`/apply/${program.slug}`" class="vl-btn-primary mt-4 inline-flex text-sm">Apply</Link>
                </div>
            </div>
        </section>
    </FounderShell>
</template>
