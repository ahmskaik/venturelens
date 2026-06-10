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
        class="vl-card p-5 transition hover:shadow-card-lg"
        :class="{
            'border-brand-200 bg-gradient-to-br from-brand-50/80 to-white': variant === 'brand',
            'border-emerald-200 bg-gradient-to-br from-emerald-50/80 to-white': variant === 'success',
        }"
    >
        <p class="text-sm font-medium text-slate-500">{{ label }}</p>
        <p class="vl-display mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ value ?? '—' }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>
