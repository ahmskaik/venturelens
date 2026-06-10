<script setup>
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';

defineProps({
    applications: Array,
});
</script>

<template>
    <AppShell
        title="Applications"
        subtitle="All startup submissions across your cohorts — open any row for Gemini scores and committee decisions."
        badge="Review queue"
    >
        <div class="vl-card overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Startup</th>
                        <th class="px-5 py-3">Founder</th>
                        <th class="px-5 py-3">Cohort</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">AI Score</th>
                        <th class="px-5 py-3">Recommendation</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="app in applications"
                        :key="app.id"
                        class="border-t border-slate-100 transition hover:bg-brand-50/20"
                    >
                        <td class="px-5 py-4 font-semibold text-slate-900">{{ app.startup_name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ app.founder_name }}</td>
                        <td class="px-5 py-4">
                            <Link
                                :href="`/programs/${app.program.id}/applications`"
                                class="font-medium text-brand-700 hover:text-brand-800 hover:underline"
                            >
                                {{ app.program.name }}
                            </Link>
                        </td>
                        <td class="px-5 py-4"><StatusBadge :status="app.status" /></td>
                        <td class="px-5 py-4">
                            <span v-if="app.ai_overall_score" class="vl-display text-lg font-bold text-brand-700">{{ app.ai_overall_score }}</span>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-5 py-4 capitalize text-slate-600">{{ app.recommendation ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <Link :href="`/applications/${app.id}`" class="vl-btn-primary py-2 text-xs">Review →</Link>
                        </td>
                    </tr>
                    <tr v-if="!applications.length">
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            No applications yet.
                            <Link href="/cohorts" class="ml-1 font-medium text-brand-600 hover:underline">Share a cohort apply link</Link>
                            to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppShell>
</template>
