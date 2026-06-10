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
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-violet-700 shadow-md ring-1 ring-white/20 transition group-hover:scale-105"
            :class="compact ? 'h-8 w-8' : ''"
        >
            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3" />
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
            </svg>
        </div>
        <div v-if="!compact" class="leading-tight">
            <span class="vl-display text-lg font-bold tracking-tight" :class="dark ? 'text-white' : 'text-slate-900'">
                Venture<span class="text-brand-600">Lens</span>
            </span>
            <span v-if="!compact" class="block text-[10px] font-medium uppercase tracking-widest" :class="dark ? 'text-slate-500' : 'text-slate-400'">
                AI-native incubation
            </span>
        </div>
    </Link>
</template>
