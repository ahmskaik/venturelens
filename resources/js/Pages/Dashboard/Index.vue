<script setup>
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatCard from '../../Components/Ui/StatCard.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

defineProps({
    organization: Object,
    stats: Object,
    programs: Array,
    recent_executions: Array,
});
</script>

<template>
    <AppShell
        :title="organization.name"
        :subtitle="`Plan: ${organization.plan} · Screenings ${organization.screenings_used} / ${organization.screenings_quota}`"
        badge="Command center"
    >
        <template #actions>
            <GeminiBadge />
        </template>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Applications today" :value="stats.applications_today" />
            <StatCard label="Screenings completed" :value="stats.screenings_completed" variant="brand" />
            <StatCard label="Avg AI score" :value="stats.avg_score ?? '—'" />
            <StatCard
                label="Gemini calls (30d)"
                :value="stats.gemini_calls_30d"
                :hint="`${stats.gemini_tokens_30d} tokens`"
                variant="success"
            />
        </div>

            <section class="mt-10">
                <div class="flex items-center justify-between">
                    <h2 class="vl-display text-lg font-bold text-slate-900">Programs</h2>
                    <Link href="/cohorts" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all cohorts →</Link>
                </div>
            <div class="mt-4 space-y-3">
                <div
                    v-for="program in programs"
                    :key="program.id"
                    class="vl-card flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:border-brand-200"
                >
                    <div>
                        <p class="font-semibold text-slate-900">{{ program.name }}</p>
                        <p class="text-sm text-slate-500">{{ program.applications_count }} applications · {{ program.status }}</p>
                    </div>
                    <Link :href="`/programs/${program.id}/applications`" class="vl-btn-primary text-sm">
                        View applications →
                    </Link>
                </div>
            </div>
        </section>

        <section class="mt-10">
            <h2 class="vl-display text-lg font-bold text-slate-900">Recent agent activity</h2>
            <p class="mt-1 text-sm text-slate-500">Screening pipeline execution log — visible proof of AI-native operations</p>
            <div class="vl-card mt-4 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Step</th>
                            <th class="px-5 py-3">Decision</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(exec, i) in recent_executions" :key="i" class="border-t border-slate-100 hover:bg-brand-50/30">
                            <td class="px-5 py-3 font-mono text-xs text-brand-700">{{ exec.step }}</td>
                            <td class="px-5 py-3 font-medium">{{ exec.decision }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ exec.action_taken }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ exec.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="!recent_executions.length">
                            <td colspan="4" class="px-5 py-10 text-center text-slate-400">
                                No executions yet — submit an application to see the pipeline.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppShell>
</template>
