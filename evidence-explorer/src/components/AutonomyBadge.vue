<script setup>
import { AUTONOMY_LABELS, AUTONOMY_CLASSES } from '../utils/format';

defineProps({
    level: { type: Number, default: 0 },
});
</script>

<template>
    <span
        class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
        :class="AUTONOMY_CLASSES[level] ?? AUTONOMY_CLASSES[0]"
    >
        {{ AUTONOMY_LABELS[level] ?? `L${level}` }}
    </span>
</template>
