<script setup>
import { AGENT_CLASSES } from '../utils/format';

defineProps({
    name: { type: String, required: true },
});
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
        :class="AGENT_CLASSES[name] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
    >
        {{ name }}
    </span>
</template>
