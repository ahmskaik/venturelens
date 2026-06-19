<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
});
</script>

<template>
    <div class="vl-card p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ value }}</p>
    </div>
</template>
