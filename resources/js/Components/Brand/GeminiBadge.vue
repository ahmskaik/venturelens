<script setup>
defineProps({
    size: { type: String, default: 'sm' },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 font-medium text-slate-600"
        :class="size === 'lg' ? 'px-3 py-1 text-sm' : 'px-2 py-0.5 text-xs'"
    >
        <svg class="h-3.5 w-3.5 text-slate-500" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none" />
        </svg>
        Gemini
    </span>
</template>
