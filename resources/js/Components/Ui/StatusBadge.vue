<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: String,
});

const classes = computed(() => {
    const map = {
        screened: 'bg-brand-100 text-brand-800 ring-brand-200',
        accepted: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        rejected: 'bg-red-100 text-red-800 ring-red-200',
        shortlisted: 'bg-amber-100 text-amber-800 ring-amber-200',
        processing: 'bg-blue-100 text-blue-800 ring-blue-200 animate-pulse',
        submitted: 'bg-slate-100 text-slate-700 ring-slate-200',
        needs_info: 'bg-orange-100 text-orange-800 ring-orange-200',
        waitlisted: 'bg-violet-100 text-violet-800 ring-violet-200',
    };
    return map[props.status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
});
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset" :class="classes">
        {{ status?.replace('_', ' ') }}
    </span>
</template>
