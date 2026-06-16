<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, default: '/' },
    dark: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <Link :href="href" class="group flex items-center gap-2.5">
        <div
            class="flex items-center justify-center rounded-lg bg-brand-600"
            :class="compact ? 'h-8 w-8' : 'h-9 w-9'"
        >
            <svg class="h-[18px] w-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </div>
        <div v-if="!compact" class="leading-tight">
            <span class="text-base font-semibold tracking-tight" :class="dark ? 'text-white' : 'text-slate-900'">
                VentureLens
            </span>
            <span v-if="!compact" class="block text-[11px] text-slate-500" :class="dark ? 'text-slate-400' : ''">
                Application screening
            </span>
        </div>
    </Link>
</template>
