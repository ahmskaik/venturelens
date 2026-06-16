<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import SeoHead from '../../Components/Seo/SeoHead.vue';
import { seoDefaults } from '../../seo/defaults.js';

const props = defineProps({
    application: Object,
    program: Object,
});

let pollInterval;

onMounted(() => {
    if (['submitted', 'processing'].includes(props.application.status)) {
        pollInterval = setInterval(() => {
            router.reload({ only: ['application'] });
        }, 4000);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const statusLabel = {
    submitted: 'Submitted',
    processing: 'Processing — Gemini is screening your application…',
    screened: 'Screened',
    needs_info: 'Needs more information',
};
</script>

<template>
    <SeoHead
        :title="seoDefaults.applyStatus.title"
        :description="seoDefaults.applyStatus.description"
        noindex
    />
    <div class="min-h-screen bg-slate-50 py-16">
        <div class="mx-auto max-w-lg rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <h1 class="text-2xl font-bold">{{ application.startup_name }}</h1>
            <p class="mt-2 text-slate-600">{{ program.name }}</p>

            <div class="mt-6 rounded-lg bg-slate-100 p-4">
                <p class="text-sm font-medium text-slate-500">Status</p>
                <p class="mt-1 text-lg font-semibold">{{ statusLabel[application.status] || application.status }}</p>
            </div>

            <div v-if="application.screening" class="mt-6 text-left">
                <p class="text-sm text-slate-500">Gemini score</p>
                <p class="text-4xl font-bold text-indigo-600">{{ application.screening.overall_score }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ application.screening.summary }}</p>
                <p class="mt-2 text-xs uppercase tracking-wide text-slate-400">Recommendation: {{ application.screening.recommendation }}</p>
            </div>

            <Link v-if="application.founder_portal_url" :href="application.founder_portal_url" class="mt-4 inline-block text-sm font-medium text-emerald-600 hover:underline">
                Open in founder portal
            </Link>
            <Link href="/" class="mt-8 inline-block text-sm text-indigo-600 hover:underline">Back to VentureLens</Link>
        </div>
    </div>
</template>
