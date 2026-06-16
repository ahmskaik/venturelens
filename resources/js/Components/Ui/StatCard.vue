<script setup>
defineProps({
    label: String,
    value: [String, Number],
    hint: String,
    variant: { type: String, default: 'default' },
});
</script>

<template>
    <div
        class="vl-card p-5"
        :class="{
            'border-l-4 border-l-brand-500': variant === 'brand',
            'border-l-4 border-l-accent-500': variant === 'success',
        }"
    >
        <p class="text-sm text-slate-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900">{{ value ?? '—' }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>
