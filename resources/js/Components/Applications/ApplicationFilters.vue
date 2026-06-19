<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    filterOptions: {
        type: Object,
        required: true,
    },
    baseUrl: {
        type: String,
        required: true,
    },
    showProgramFilter: {
        type: Boolean,
        default: false,
    },
});

const local = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    program: props.filters.program ?? '',
    sector: props.filters.sector ?? '',
    country: props.filters.country ?? '',
    recommendation: props.filters.recommendation ?? '',
    screened_only: props.filters.screened_only ?? false,
    sort: props.filters.sort ?? 'submitted_desc',
    per_page: props.filters.per_page ?? 25,
});

const hasActiveFilters = computed(() => {
    return Boolean(
        local.q
        || local.status
        || local.program
        || local.sector
        || local.country
        || local.recommendation
        || local.screened_only,
    );
});

let searchTimer = null;
let syncing = false;

function submit(resetPage = true) {
    const params = {
        q: local.q || undefined,
        status: local.status || undefined,
        program: local.program || undefined,
        sector: local.sector || undefined,
        country: local.country || undefined,
        recommendation: local.recommendation || undefined,
        screened_only: local.screened_only ? 1 : undefined,
        sort: local.sort || undefined,
        per_page: local.per_page || undefined,
    };

    if (resetPage) {
        params.page = 1;
    }

    router.get(props.baseUrl, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    local.q = '';
    local.status = '';
    local.program = '';
    local.sector = '';
    local.country = '';
    local.recommendation = '';
    local.screened_only = false;
    local.sort = 'submitted_desc';
    local.per_page = 25;
    submit();
}

watch(
    () => props.filters,
    (next) => {
        syncing = true;
        local.q = next.q ?? '';
        local.status = next.status ?? '';
        local.program = next.program ?? '';
        local.sector = next.sector ?? '';
        local.country = next.country ?? '';
        local.recommendation = next.recommendation ?? '';
        local.screened_only = next.screened_only ?? false;
        local.sort = next.sort ?? 'submitted_desc';
        local.per_page = next.per_page ?? 25;
        syncing = false;
    },
    { deep: true },
);

watch(
    () => local.q,
    () => {
        if (syncing) {
            return;
        }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => submit(), 350);
    },
);

watch(
    () => [local.status, local.program, local.sector, local.country, local.recommendation, local.screened_only, local.sort, local.per_page],
    () => {
        if (syncing) {
            return;
        }
        submit();
    },
);
</script>

<template>
    <div class="border-b border-slate-100 px-5 py-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filters</h2>
                <p class="mt-0.5 text-xs text-slate-500">Narrow the list — filters apply across all pages</p>
            </div>
            <button
                v-if="hasActiveFilters"
                type="button"
                class="vl-btn-ghost text-xs"
                @click="clearFilters"
            >
                Clear filters
            </button>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <label class="block sm:col-span-2 lg:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-600">Search</span>
                <input
                    v-model="local.q"
                    type="search"
                    placeholder="Startup, founder, or email…"
                    class="vl-input"
                />
            </label>

            <label v-if="showProgramFilter" class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Cohort</span>
                <select v-model="local.program" class="vl-input">
                    <option value="">All cohorts</option>
                    <option v-for="program in filterOptions.programs" :key="program.id" :value="String(program.id)">
                        {{ program.name }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Status</span>
                <select v-model="local.status" class="vl-input">
                    <option value="">All statuses</option>
                    <option v-for="status in filterOptions.statuses" :key="status" :value="status" class="capitalize">
                        {{ status.replace('_', ' ') }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">AI recommendation</span>
                <select v-model="local.recommendation" class="vl-input">
                    <option value="">Any</option>
                    <option v-for="rec in filterOptions.recommendations" :key="rec" :value="rec" class="capitalize">
                        {{ rec.replace('_', ' ') }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Sector</span>
                <select v-model="local.sector" class="vl-input">
                    <option value="">All sectors</option>
                    <option v-for="sector in filterOptions.sectors" :key="sector" :value="sector">
                        {{ sector }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Country</span>
                <select v-model="local.country" class="vl-input">
                    <option value="">All countries</option>
                    <option
                        v-for="country in filterOptions.countries"
                        :key="country.code ?? country"
                        :value="country.code ?? country"
                    >
                        {{ country.name ?? country }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Sort</span>
                <select v-model="local.sort" class="vl-input">
                    <option v-for="(label, key) in filterOptions.sorts" :key="key" :value="key">
                        {{ label }}
                    </option>
                </select>
            </label>

            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-600">Per page</span>
                <select v-model.number="local.per_page" class="vl-input">
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
            </label>

            <label class="flex items-end gap-2 pb-2 sm:col-span-2">
                <input
                    id="screened-only"
                    v-model="local.screened_only"
                    type="checkbox"
                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                />
                <span class="text-sm text-slate-600">Screened only (has AI score)</span>
            </label>
        </div>
    </div>
</template>
