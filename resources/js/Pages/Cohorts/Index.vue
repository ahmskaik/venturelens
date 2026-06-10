<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../Components/Layout/AppShell.vue';

defineProps({
    programs: Array,
});

const copiedSlug = ref(null);

function copyApplyUrl(url, slug) {
    navigator.clipboard.writeText(url);
    copiedSlug.value = slug;
    setTimeout(() => {
        copiedSlug.value = null;
    }, 2000);
}

function formatDate(iso) {
    if (!iso) return '—';
    return iso.slice(0, 10);
}
</script>

<template>
    <AppShell
        title="Cohorts"
        subtitle="Manage open programs, share apply links with founders, and review submissions per cohort."
        badge="Programs"
    >
        <div v-if="programs.length" class="space-y-4">
            <div
                v-for="program in programs"
                :key="program.id"
                class="vl-card-elevated p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="vl-display text-xl font-bold text-slate-900">{{ program.name }}</h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset"
                                :class="program.status === 'open'
                                    ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                                    : 'bg-slate-100 text-slate-700 ring-slate-200'"
                            >
                                {{ program.status }}
                            </span>
                            <span
                                v-if="program.status === 'open' && !program.accepting"
                                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
                            >
                                Not accepting
                            </span>
                        </div>
                        <p v-if="program.description" class="mt-2 text-sm text-slate-600">{{ program.description }}</p>
                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                            <div>
                                <dt class="text-slate-400">Applications</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ program.applications_count }}{{ program.max_applications ? ` / ${program.max_applications}` : '' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Opens</dt>
                                <dd class="font-medium">{{ formatDate(program.opens_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-400">Closes</dt>
                                <dd class="font-medium">{{ formatDate(program.closes_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="`/programs/${program.id}/applications`" class="vl-btn-primary text-sm">
                            View applications →
                        </Link>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/50 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-700">Founder apply link</p>
                        <p class="mt-1 truncate font-mono text-sm text-slate-700">{{ program.apply_url }}</p>
                    </div>
                    <button
                        type="button"
                        class="vl-btn-secondary shrink-0 text-sm"
                        @click="copyApplyUrl(program.apply_url, program.slug)"
                    >
                        {{ copiedSlug === program.slug ? 'Copied!' : 'Copy link' }}
                    </button>
                    <a :href="program.apply_url" target="_blank" rel="noopener" class="vl-btn-ghost shrink-0 text-sm text-brand-700">
                        Preview →
                    </a>
                </div>
            </div>
        </div>

        <div v-else class="vl-card border-dashed p-12 text-center">
            <p class="text-slate-600">No cohorts yet.</p>
            <p class="mt-2 text-sm text-slate-400">
                The Onboarding Agent creates your first program after signup, or run
                <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan agents:run-onboarding</code>.
            </p>
        </div>
    </AppShell>
</template>
