<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationFilters from '../../Components/Applications/ApplicationFilters.vue';
import AppShell from '../../Components/Layout/AppShell.vue';
import PaginationBar from '../../Components/Ui/PaginationBar.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';

const props = defineProps({
    program: Object,
    applications: Object,
    filters: Object,
    filterOptions: Object,
});

const totalCount = computed(() => props.applications?.total ?? props.applications?.meta?.total ?? 0);
</script>

<template>
    <AppShell
        :title="program.name"
        :subtitle="totalCount
            ? `${totalCount.toLocaleString()} applications in this cohort.`
            : 'Review AI-screened applications and open any row for committee decision.'"
    >
        <template #actions>
            <Link href="/cohorts" class="vl-btn-secondary text-sm">All cohorts</Link>
        </template>

        <div class="vl-card overflow-hidden">
            <ApplicationFilters
                :filters="filters"
                :filter-options="filterOptions"
                :base-url="`/programs/${program.id}/applications`"
            />

            <div class="overflow-x-auto">
                <table class="vl-data-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Startup</th>
                            <th>Founder</th>
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
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                {{ filters.q || filters.status ? 'No applications match the current filters.' : 'No applications yet.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar :pagination="applications" />
        </div>
    </AppShell>
</template>
