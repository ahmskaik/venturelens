<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
    },
});

/** Laravel paginator exposes meta at root; Inertia v2 may nest under `meta`. */
const meta = computed(() => {
    const p = props.pagination ?? {};
    if (p.meta && typeof p.meta.total === 'number') {
        return p.meta;
    }

    return {
        current_page: p.current_page ?? 1,
        from: p.from ?? 0,
        to: p.to ?? 0,
        total: p.total ?? 0,
        last_page: p.last_page ?? 1,
        per_page: p.per_page ?? 25,
    };
});

const links = computed(() => props.pagination?.links ?? props.pagination?.meta?.links ?? []);
</script>

<template>
    <div v-if="meta.total > 0" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4">
        <p class="text-sm text-slate-500">
            Showing
            <span class="font-medium text-slate-700">{{ meta.from }}</span>
            –
            <span class="font-medium text-slate-700">{{ meta.to }}</span>
            of
            <span class="font-medium text-slate-700">{{ meta.total }}</span>
        </p>
        <nav v-if="meta.last_page > 1" class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <Link
                v-for="(link, index) in links"
                :key="index"
                :href="link.url ?? '#'"
                class="min-w-[2.25rem] rounded-lg px-3 py-1.5 text-center text-sm transition"
                :class="[
                    link.active
                        ? 'bg-brand-600 font-medium text-white'
                        : link.url
                          ? 'text-slate-600 hover:bg-white hover:shadow-sm'
                          : 'cursor-not-allowed text-slate-300',
                ]"
                :preserve-scroll="true"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
