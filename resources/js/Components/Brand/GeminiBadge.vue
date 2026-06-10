<script setup>
defineProps({
    size: { type: String, default: 'sm' },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border border-violet-200/80 bg-gradient-to-r from-violet-50 to-blue-50 font-medium text-violet-800"
        :class="size === 'lg' ? 'px-4 py-1.5 text-sm' : 'px-2.5 py-1 text-xs'"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="1.5" fill="none" />
        </svg>
        Powered by Gemini
    </span>
</template>
