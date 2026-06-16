<script setup>
import { Link } from '@inertiajs/vue3';
import FounderShell from '../../../Components/Layout/FounderShell.vue';

defineProps({
    programs: Array,
});
</script>

<template>
    <FounderShell title="Open programs" subtitle="Apply to cohorts accepting startups.">
        <div class="grid gap-4">
            <div v-for="program in programs" :key="program.id" class="vl-card flex flex-wrap items-start justify-between gap-4 p-6">
                <div class="max-w-2xl">
                    <p class="text-sm font-medium text-emerald-600">{{ program.organization }}</p>
                    <h2 class="vl-display mt-1 text-xl font-bold">{{ program.name }}</h2>
                    <p class="mt-2 text-slate-600">{{ program.description }}</p>
                    <p v-if="program.already_applied" class="mt-3 text-sm font-medium text-brand-700">You already applied to this program.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <Link
                        v-if="program.accepting && !program.already_applied"
                        :href="program.apply_url"
                        class="vl-btn-primary"
                    >
                        Apply
                    </Link>
                    <span v-else-if="!program.accepting" class="text-sm text-slate-500">Not accepting applications</span>
                    <Link v-else href="/founder/applications" class="vl-btn-secondary text-sm">View application</Link>
                </div>
            </div>
            <p v-if="!programs.length" class="vl-card py-12 text-center text-slate-500">No open programs right now.</p>
        </div>
    </FounderShell>
</template>
