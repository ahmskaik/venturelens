<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../Brand/Logo.vue';
import NavIcon from '../Ui/NavIcon.vue';
import UserMenu from './UserMenu.vue';
import SeoHead from '../Seo/SeoHead.vue';

const props = defineProps({
    title: String,
    subtitle: String,
    badge: String,
    home: { type: Boolean, default: false },
    breadcrumb: { type: String, default: '' },
});

const page = usePage();

const primaryNav = [
    { href: '/dashboard', label: 'Home', icon: 'dashboard' },
    { href: '/cohorts', label: 'Cohorts', icon: 'cohorts' },
    { href: '/applications', label: 'Applications', icon: 'applications' },
    { href: '/billing', label: 'Billing', icon: 'billing' },
    { href: '/settings', label: 'Settings', icon: 'settings' },
];

const pinnedNav = [
    { href: '/ask', label: 'Ask', icon: 'chat' },
    { href: '/ai-operations', label: 'AI Operations', icon: 'ai' },
    { href: '/impact', label: 'Impact', icon: 'impact', external: true },
];

const currentPath = computed(() => page.url.split('?')[0]);
const user = computed(() => page.props.auth?.user);

const crumbLabel = computed(() => {
    if (props.breadcrumb) return props.breadcrumb;
    if (props.home) return 'Home';
    return props.title ?? 'Home';
});

const usagePercent = computed(() => {
    const org = page.props.auth?.organization;
    if (!org?.screenings_quota) return 0;
    return Math.min(100, Math.round((org.screenings_used / org.screenings_quota) * 100));
});

function isActive(href) {
    const path = currentPath.value;

    switch (href) {
        case '/dashboard':
            return path === '/dashboard';
        case '/cohorts':
            return path === '/cohorts' || /^\/programs\/\d+\/applications$/.test(path);
        case '/applications':
            return path === '/applications' || /^\/applications\/\d+$/.test(path);
        case '/settings':
            return path === '/settings' || path.startsWith('/settings/');
        case '/ask':
            return path === '/ask';
        default:
            return path === href || path.startsWith(`${href}/`);
    }
}
</script>

<template>
    <SeoHead
        :title="title || crumbLabel"
        :description="subtitle || 'VentureLens incubator workspace — manage cohorts, AI-screened applications, and committee decisions.'"
        noindex
    />
    <div class="min-h-screen bg-white">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-[248px] flex-col border-r border-slate-200 bg-white lg:flex">
            <div class="px-4 py-4">
                <Logo />
            </div>

            <div v-if="page.props.auth?.organization" class="mx-3 mb-2">
                <Link
                    href="/settings"
                    class="flex w-full items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-left transition hover:border-slate-300 hover:bg-slate-100"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-xs font-semibold text-white">
                        {{ page.props.auth.organization.name?.[0]?.toUpperCase() }}
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-900">
                        {{ page.props.auth.organization.name }}
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15M12 2.25v16.5" />
                    </svg>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-2">
                <Link
                    v-for="item in primaryNav"
                    :key="item.href"
                    :href="item.href"
                    class="vl-sidebar-link"
                    :class="{ 'vl-sidebar-link-active': isActive(item.href) }"
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </Link>

                <p class="vl-sidebar-section">Shortcuts</p>
                <Link
                    v-for="item in pinnedNav"
                    :key="item.href"
                    :href="item.href"
                    :target="item.external ? '_blank' : undefined"
                    class="vl-sidebar-link"
                    :class="{ 'vl-sidebar-link-active': !item.external && isActive(item.href) }"
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </Link>
            </nav>

            <div class="space-y-3 border-t border-slate-100 p-4">
                <div v-if="page.props.auth?.organization" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Screenings used</span>
                        <span class="font-medium text-slate-700">
                            {{ page.props.auth.organization.screenings_used }} / {{ page.props.auth.organization.screenings_quota }}
                        </span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: `${usagePercent}%` }" />
                    </div>
                    <Link href="/billing" class="mt-3 flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">
                        Manage plan
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Mobile header -->
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white lg:hidden">
            <div class="flex items-center justify-between px-4 py-3">
                <Logo compact />
                <nav class="flex gap-0.5 overflow-x-auto text-xs">
                    <Link href="/dashboard" class="vl-btn-ghost shrink-0 px-2">Home</Link>
                    <Link href="/applications" class="vl-btn-ghost shrink-0 px-2">Apps</Link>
                    <Link href="/ask" class="vl-btn-ghost shrink-0 px-2">Ask</Link>
                    <Link href="/settings" class="vl-btn-ghost shrink-0 px-2">Settings</Link>
                </nav>
            </div>
        </header>

        <!-- Main column -->
        <div class="lg:pl-[248px]">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 hidden border-b border-slate-200 bg-white/95 backdrop-blur-sm lg:block">
                <div class="flex h-14 items-center justify-between px-6">
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                        </svg>
                        <span class="font-medium text-slate-900">{{ crumbLabel }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <a href="/impact" target="_blank" class="vl-topbar-btn hidden xl:inline-flex">Impact</a>
                        <Link href="/ask" class="vl-ask-btn">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM16.5 7.5h.008v.008H16.5V7.5z" />
                            </svg>
                            Ask
                        </Link>
                        <UserMenu
                            v-if="user"
                            :user="user"
                            :organization="page.props.auth?.organization"
                        />
                    </div>
                </div>
            </header>

            <!-- Page header (inner pages) -->
            <header v-if="title && !home" class="border-b border-slate-100 bg-white">
                <div class="mx-auto max-w-7xl px-6 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p v-if="badge" class="vl-page-eyebrow">{{ badge }}</p>
                            <h1 class="text-xl font-semibold tracking-tight text-slate-900" :class="{ 'mt-1': badge }">{{ title }}</h1>
                            <p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-slate-600">{{ subtitle }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <slot name="actions" />
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-6 py-6 lg:py-8">
                <slot />
            </main>
        </div>
    </div>
</template>
