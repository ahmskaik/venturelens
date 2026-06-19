<script setup>
import { computed } from 'vue';

const props = defineProps({
    byAgent: { type: Object, default: () => ({}) },
});

const max = computed(() => Math.max(...Object.values(props.byAgent), 1));

const rows = computed(() =>
    Object.entries(props.byAgent)
        .sort((a, b) => b[1] - a[1])
        .map(([name, count]) => ({
            name,
            count,
            width: `${Math.round((count / max.value) * 100)}%`,
        })),
);
</script>

<template>
    <div class="vl-card p-5">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Actions by agent</h3>
        <ul class="mt-4 space-y-3">
            <li v-for="row in rows" :key="row.name" class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="capitalize text-slate-700 dark:text-slate-300">{{ row.name }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ row.count }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                    <div
                        class="h-full rounded-full bg-brand-600 transition-all"
                        :style="{ width: row.width }"
                    />
                </div>
            </li>
        </ul>
    </div>
</template>
