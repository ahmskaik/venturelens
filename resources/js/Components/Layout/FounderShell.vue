<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../Brand/Logo.vue';
import NavIcon from '../Ui/NavIcon.vue';
import SeoHead from '../Seo/SeoHead.vue';

const props = defineProps({
    title: String,
    subtitle: String,
    badge: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const nav = [
    { href: '/founder/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { href: '/founder/applications', label: 'My applications', icon: 'applications' },
    { href: '/founder/programs', label: 'Programs', icon: 'programs' },
    { href: '/founder/settings', label: 'Profile', icon: 'settings' },
];

const currentPath = computed(() => page.url.split('?')[0]);

function isActive(href) {
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}
</script>

<template>
    <SeoHead
        :title="props.title || 'Founder workspace'"
        :description="props.subtitle || 'VentureLens founder portal — track accelerator applications and screening results.'"
        noindex
    />
    <div class="min-h-screen bg-slate-50">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-slate-200 bg-white lg:flex">
            <div class="border-b border-slate-100 px-5 py-4">
                <Logo />
                <p class="mt-1.5 text-xs font-medium text-slate-500">Founder portal</p>
            </div>

            <nav class="flex-1 space-y-0.5 px-3 py-4">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="vl-sidebar-link"
                    :class="{ 'vl-sidebar-link-active': isActive(item.href) }"
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <Link
                    v-if="user"
                    href="/founder/settings"
                    class="mb-3 flex items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-slate-50"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700">
                        {{ user.name?.[0]?.toUpperCase() }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">{{ user.name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
                    </div>
                </Link>
                <Link href="/logout" method="post" as="button" class="vl-btn-ghost w-full justify-start text-slate-500">
                    Sign out
                </Link>
            </div>
        </aside>

        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white lg:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <Logo compact />
                <nav class="flex gap-0.5 text-xs">
                    <Link href="/founder/dashboard" class="vl-btn-ghost px-2">Home</Link>
                    <Link href="/founder/applications" class="vl-btn-ghost px-2">Apps</Link>
                    <Link href="/founder/programs" class="vl-btn-ghost px-2">Programs</Link>
                </nav>
            </div>
        </header>

        <div class="lg:pl-60">
            <header v-if="title" class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-6 py-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p v-if="badge" class="vl-page-eyebrow">{{ badge }}</p>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-900" :class="{ 'mt-1': badge }">{{ title }}</h1>
                            <p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-slate-600">{{ subtitle }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <slot name="actions" />
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-6xl px-6 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
