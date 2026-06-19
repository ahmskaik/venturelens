<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationFilters from '../../Components/Applications/ApplicationFilters.vue';
import AppShell from '../../Components/Layout/AppShell.vue';
import PaginationBar from '../../Components/Ui/PaginationBar.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';

const props = defineProps({
    applications: Object,
    filters: Object,
    filterOptions: Object,
});

const totalCount = computed(() => props.applications?.total ?? props.applications?.meta?.total ?? 0);
</script>

<template>
    <AppShell
        title="Applications"
        :subtitle="totalCount
            ? `${totalCount.toLocaleString()} startup submissions across your cohorts.`
            : 'All startup submissions across your cohorts.'"
    >
        <div class="vl-card overflow-hidden">
            <ApplicationFilters
                :filters="filters"
                :filter-options="filterOptions"
                base-url="/applications"
                show-program-filter
            />

            <div class="overflow-x-auto">
                <table class="vl-data-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Startup</th>
                            <th>Founder</th>
                            <th>Cohort</th>
                            <th>Status</th>
                            <th>AI Score</th>
                            <th>Recommendation</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="app in applications.data" :key="app.id">
                            <td class="font-medium text-slate-900">{{ app.startup_name }}</td>
                            <td class="text-slate-600">{{ app.founder_name }}</td>
                            <td>
                                <Link
                                    :href="`/programs/${app.program.id}/applications`"
                                    class="font-medium text-brand-700 hover:underline"
                                >
                                    {{ app.program.name }}
                                </Link>
                            </td>
                            <td><StatusBadge :status="app.status" /></td>
                            <td>
                                <span v-if="app.ai_overall_score" class="text-lg font-semibold tabular-nums text-brand-700">{{ app.ai_overall_score }}</span>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="capitalize text-slate-600">{{ app.recommendation?.replace('_', ' ') ?? '—' }}</td>
                            <td>
                                <Link :href="`/applications/${app.id}`" class="vl-btn-secondary py-1.5 text-xs">Review</Link>
                            </td>
                        </tr>
                        <tr v-if="!applications.data?.length">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <template v-if="filters.q || filters.status || filters.program">
                                    No applications match the current filters.
                                </template>
                                <template v-else>
                                    No applications yet.
                                    <Link href="/cohorts" class="ml-1 font-medium text-brand-600 hover:underline">Share a cohort apply link</Link>
                                    to get started.
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar :pagination="applications" />
        </div>
    </AppShell>
</template>
