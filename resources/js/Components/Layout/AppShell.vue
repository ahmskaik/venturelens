<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../Brand/Logo.vue';
import GeminiBadge from '../Brand/GeminiBadge.vue';

const props = defineProps({
    title: String,
    subtitle: String,
    badge: String,
});

const page = usePage();

const nav = [
    { href: '/dashboard', label: 'Dashboard' },
    { href: '/cohorts', label: 'Cohorts' },
    { href: '/applications', label: 'Applications' },
    { href: '/ai-operations', label: 'AI Operations' },
    { href: '/impact', label: 'Impact' },
    { href: '/billing', label: 'Billing' },
];

const currentPath = computed(() => page.url.split('?')[0]);

function isActive(href) {
    const path = currentPath.value;

    switch (href) {
        case '/dashboard':
            return path === '/dashboard';
        case '/cohorts':
            return path === '/cohorts' || /^\/programs\/\d+\/applications$/.test(path);
        case '/applications':
            return path === '/applications' || /^\/applications\/\d+$/.test(path);
        default:
            return path === href || path.startsWith(`${href}/`);
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-800/50 bg-slate-950 lg:flex">
            <div class="border-b border-white/5 px-5 py-5">
                <Logo dark />
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="vl-sidebar-link"
                    :class="{ 'vl-sidebar-link-active': isActive(item.href) }"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/5 text-sm">●</span>
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-white/5 p-4">
                <GeminiBadge />
                <p class="text-xs text-slate-500">Build with Gemini XPRIZE</p>
                <Link href="/logout" method="post" as="button" class="vl-btn-ghost w-full justify-start text-slate-400 hover:text-white">
                    Sign out
                </Link>
            </div>
        </aside>

        <!-- Mobile top bar -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur lg:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <Logo compact />
                <nav class="flex gap-1 text-xs">
                    <Link href="/dashboard" class="vl-btn-ghost px-2">Home</Link>
                    <Link href="/cohorts" class="vl-btn-ghost px-2">Cohorts</Link>
                    <Link href="/applications" class="vl-btn-ghost px-2">Apps</Link>
                    <Link href="/ai-operations" class="vl-btn-ghost px-2">AI Ops</Link>
                </nav>
            </div>
        </header>

        <!-- Main -->
        <div class="lg:pl-64">
            <header v-if="title" class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p v-if="badge" class="mb-2 text-xs font-semibold uppercase tracking-wider text-brand-600">{{ badge }}</p>
                            <h1 class="vl-display text-3xl font-bold tracking-tight text-slate-900">{{ title }}</h1>
                            <p v-if="subtitle" class="mt-2 max-w-2xl text-slate-600">{{ subtitle }}</p>
                        </div>
                        <slot name="actions" />
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-6 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
