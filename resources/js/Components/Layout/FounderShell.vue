<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../Brand/Logo.vue';

defineProps({
    title: String,
    subtitle: String,
    badge: String,
});
const page = usePage();
const user = computed(() => page.props.auth?.user);

const nav = [
    { href: '/founder/dashboard', label: 'Dashboard' },
    { href: '/founder/applications', label: 'My applications' },
    { href: '/founder/programs', label: 'Programs' },
    { href: '/founder/settings', label: 'Profile' },
];

const currentPath = computed(() => page.url.split('?')[0]);

function isActive(href) {
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-emerald-900/30 bg-slate-950 lg:flex">
            <div class="border-b border-white/5 px-5 py-5">
                <Logo dark />
                <p class="mt-2 text-xs font-medium uppercase tracking-wider text-emerald-400">Founder portal</p>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="vl-sidebar-link"
                    :class="{ 'vl-sidebar-link-active': isActive(item.href) }"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-sm text-emerald-300">●</span>
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-white/5 p-4">
                <Link href="/logout" method="post" as="button" class="vl-btn-ghost w-full justify-start text-slate-400 hover:text-white">
                    Sign out
                </Link>
            </div>
        </aside>

        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur lg:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <Logo compact />
                <nav class="flex gap-1 text-xs">
                    <Link href="/founder/dashboard" class="vl-btn-ghost px-2">Home</Link>
                    <Link href="/founder/applications" class="vl-btn-ghost px-2">Apps</Link>
                    <Link href="/founder/programs" class="vl-btn-ghost px-2">Programs</Link>
                </nav>
            </div>
        </header>

        <div class="lg:pl-64">
            <header v-if="title" class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p v-if="badge" class="mb-2 text-xs font-semibold uppercase tracking-wider text-emerald-600">{{ badge }}</p>
                            <h1 class="vl-display text-3xl font-bold tracking-tight text-slate-900">{{ title }}</h1>
                            <p v-if="subtitle" class="mt-2 max-w-2xl text-slate-600">{{ subtitle }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <slot name="actions" />
                            <Link
                                v-if="user"
                                href="/founder/settings"
                                class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm"
                            >
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-800">
                                    {{ user.name?.[0]?.toUpperCase() }}
                                </span>
                                <span class="hidden sm:inline">{{ user.name }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-6 py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
