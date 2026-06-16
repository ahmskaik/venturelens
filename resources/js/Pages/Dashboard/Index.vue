<script setup>
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import NavIcon from '../../Components/Ui/NavIcon.vue';
import StatusBadge from '../../Components/Ui/StatusBadge.vue';

const props = defineProps({
    organization: Object,
    stats: Object,
    programs: Array,
    recent_applications: Array,
    recent_executions: Array,
});

const quickActions = [
    { href: '/applications', label: 'Applications', icon: 'applications' },
    { href: '/cohorts', label: 'Cohorts', icon: 'cohorts' },
    { href: '/ai-operations', label: 'AI Operations', icon: 'ai' },
    { href: '/billing', label: 'Billing', icon: 'billing' },
    { href: '/settings', label: 'Settings', icon: 'settings' },
];

const featureCards = [
    {
        href: '/applications',
        title: 'Review applications',
        description: 'Screen submissions, read AI scores, and record committee decisions.',
        preview: 'applications',
    },
    {
        href: '/cohorts',
        title: 'Manage cohorts',
        description: 'Open programs, share apply links, and track intake per cohort.',
        preview: 'cohorts',
    },
    {
        href: '/ai-operations',
        title: 'AI operations',
        description: 'Monitor agents, autonomy levels, and screening pipeline activity.',
        preview: 'ai',
    },
];

function timeAgo(iso) {
    if (!iso) return '';
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${Math.max(mins, 1)} min ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    const days = Math.floor(hours / 24);
    return `${days} day${days === 1 ? '' : 's'} ago`;
}

const usagePercent = Math.min(
    100,
    Math.round((props.organization.screenings_used / props.organization.screenings_quota) * 100)
);
</script>

<template>
    <AppShell home>
        <!-- Hero -->
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
                    What would you like to do?
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ organization.name }} · {{ organization.plan }} plan
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                {{ stats.screenings_completed }} screenings completed
            </span>
        </div>

        <!-- Feature cards -->
        <div class="grid gap-4 lg:grid-cols-3">
            <Link
                v-for="card in featureCards"
                :key="card.href"
                :href="card.href"
                class="vl-feature-card group"
            >
                <div class="relative flex h-36 items-center justify-center overflow-hidden bg-slate-100">
                    <!-- Applications preview -->
                    <div v-if="card.preview === 'applications'" class="w-4/5 space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="h-2 w-16 rounded bg-slate-200" />
                            <div class="h-4 w-8 rounded-full bg-brand-100" />
                        </div>
                        <div class="space-y-1.5">
                            <div class="h-2 w-full rounded bg-slate-100" />
                            <div class="h-2 w-3/4 rounded bg-slate-100" />
                        </div>
                        <div class="flex gap-1 pt-1">
                            <div class="h-5 flex-1 rounded bg-brand-600/90" />
                            <div class="h-5 w-10 rounded border border-slate-200 bg-white" />
                        </div>
                    </div>
                    <!-- Cohorts preview -->
                    <div v-else-if="card.preview === 'cohorts'" class="grid w-4/5 grid-cols-2 gap-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                            <div class="h-2 w-10 rounded bg-slate-200" />
                            <div class="mt-2 h-8 rounded bg-slate-50" />
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                            <div class="h-2 w-8 rounded bg-slate-200" />
                            <div class="mt-2 h-8 rounded bg-brand-50" />
                        </div>
                        <div class="col-span-2 rounded-lg border border-dashed border-slate-300 bg-white/80 px-2 py-1.5 text-center text-[10px] text-slate-400">
                            + New cohort
                        </div>
                    </div>
                    <!-- AI ops preview -->
                    <div v-else class="flex w-4/5 items-end gap-1 px-2">
                        <div class="h-10 flex-1 rounded-t bg-slate-300/60" />
                        <div class="h-16 flex-1 rounded-t bg-brand-400/70" />
                        <div class="h-8 flex-1 rounded-t bg-slate-300/40" />
                        <div class="h-12 flex-1 rounded-t bg-brand-300/60" />
                        <div class="h-6 flex-1 rounded-t bg-slate-300/50" />
                    </div>
                </div>
                <div class="p-4">
                    <h2 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ card.title }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ card.description }}</p>
                </div>
            </Link>
        </div>

        <!-- Quick action pills -->
        <div class="mt-6 flex flex-wrap gap-2">
            <Link v-for="action in quickActions" :key="action.href" :href="action.href" class="vl-pill-btn">
                <span class="text-slate-500"><NavIcon :name="action.icon" /></span>
                {{ action.label }}
            </Link>
            <Link href="/ask" class="vl-pill-btn border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
                Ask
            </Link>
        </div>

        <!-- Usage banner -->
        <div class="vl-promo-banner mt-10">
            <div class="grid lg:grid-cols-[1fr_auto]">
                <div class="p-6 lg:p-8">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Plan usage</p>
                    <h2 class="mt-2 text-xl font-semibold text-slate-900">
                        {{ organization.screenings_used }} of {{ organization.screenings_quota }} screenings used
                    </h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600">
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" />
                            {{ stats.applications_today }} applications received today
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" />
                            Average AI score: {{ stats.avg_score ?? '—' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" />
                            {{ stats.gemini_calls_30d }} Gemini calls in the last 30 days
                        </li>
                    </ul>
                    <Link href="/billing" class="vl-btn-primary mt-5 inline-flex text-sm">
                        Manage subscription
                    </Link>
                </div>
                <div class="flex items-center justify-center border-t border-slate-200 bg-white p-8 lg:w-72 lg:border-t-0 lg:border-l">
                    <div class="text-center">
                        <div class="relative mx-auto h-24 w-24">
                            <svg class="h-24 w-24 -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8" />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="42"
                                    fill="none"
                                    stroke="#4f46e5"
                                    stroke-width="8"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${usagePercent * 2.64} 264`"
                                />
                            </svg>
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-semibold tabular-nums text-slate-900">
                                {{ usagePercent }}%
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Quota used</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Programs -->
        <section class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Programs</h2>
                <Link href="/cohorts" class="text-sm font-medium text-slate-600 hover:text-slate-900">View all</Link>
            </div>
            <div v-if="programs.length" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="program in programs"
                    :key="program.id"
                    :href="`/programs/${program.id}/applications`"
                    class="vl-recent-card"
                >
                    <div class="flex h-28 items-center justify-center bg-gradient-to-br from-slate-100 to-slate-50">
                        <div class="text-center">
                            <p class="text-2xl font-semibold tabular-nums text-slate-900">{{ program.applications_count }}</p>
                            <p class="text-xs text-slate-500">applications</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <p class="truncate font-medium text-slate-900">{{ program.name }}</p>
                        <p class="mt-0.5 text-xs capitalize text-slate-500">{{ program.status }}</p>
                    </div>
                </Link>
            </div>
            <div v-else class="vl-card mt-4 p-8 text-center text-sm text-slate-500">
                No programs yet.
                <Link href="/cohorts" class="font-medium text-brand-600 hover:underline">Create a cohort</Link>
            </div>
        </section>

        <!-- Recents -->
        <section class="mt-10">
            <h2 class="text-lg font-semibold text-slate-900">Recents</h2>
            <div v-if="recent_applications?.length" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="app in recent_applications"
                    :key="app.id"
                    :href="`/applications/${app.id}`"
                    class="vl-recent-card"
                >
                    <div class="relative flex h-28 items-center justify-center bg-slate-900">
                        <span class="text-3xl font-semibold tabular-nums text-white">
                            {{ app.ai_overall_score ?? '—' }}
                        </span>
                        <span class="absolute bottom-2 right-2 rounded-md bg-white/10 px-2 py-0.5 text-[10px] font-medium text-white backdrop-blur">
                            Screening
                        </span>
                    </div>
                    <div class="p-3">
                        <p class="truncate font-medium text-slate-900">{{ app.startup_name }}</p>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="truncate text-xs text-slate-500">{{ app.program_name }}</p>
                            <span class="shrink-0 text-xs text-slate-400">{{ timeAgo(app.submitted_at) }}</span>
                        </div>
                        <div class="mt-2">
                            <StatusBadge :status="app.status" />
                        </div>
                    </div>
                </Link>
            </div>
            <div v-else class="vl-card mt-4 p-8 text-center text-sm text-slate-500">
                No applications yet. Share a cohort apply link to get started.
            </div>
        </section>
    </AppShell>
</template>
