<script setup>
import { Link } from '@inertiajs/vue3';
import FounderShell from '../../../Components/Layout/FounderShell.vue';
import StatusBadge from '../../../Components/Ui/StatusBadge.vue';

defineProps({
    applications: Array,
});
</script>

<template>
    <FounderShell title="My applications" subtitle="All cohort applications linked to your account." badge="Applications">
        <div class="space-y-3">
            <Link
                v-for="app in applications"
                :key="app.id"
                :href="`/founder/applications/${app.id}`"
                class="vl-card flex flex-wrap items-center justify-between gap-4 px-5 py-4 transition hover:border-emerald-200"
            >
                <div>
                    <p class="font-semibold text-slate-900">{{ app.startup_name }}</p>
                    <p class="text-sm text-slate-500">{{ app.program.name }} · {{ app.organization }}</p>
                    <p class="mt-1 text-xs text-slate-400">Submitted {{ app.submitted_at?.slice(0, 10) ?? '—' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span v-if="app.ai_overall_score" class="rounded-lg bg-brand-50 px-3 py-1 text-sm font-bold text-brand-700">
                        Score {{ app.ai_overall_score }}
                    </span>
                    <StatusBadge :status="app.status" />
                    <span v-if="app.editable" class="text-xs font-medium text-emerald-600">Editable</span>
                </div>
            </Link>
            <p v-if="!applications.length" class="vl-card py-12 text-center text-slate-500">
                No applications linked yet.
                <Link href="/founder/programs" class="block mt-2 font-medium text-emerald-600">Find a program to apply</Link>
            </p>
        </div>
    </FounderShell>
</template>
