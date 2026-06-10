<script setup>
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    application: Object,
    program: Object,
});

function rescreen() {
    router.post(`/applications/${props.application.id}/rescreen`);
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4">
            <Link :href="`/programs/${program.id}/applications`" class="text-sm text-indigo-600 hover:underline">← Applications</Link>
            <div class="mt-2 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ application.startup_name }}</h1>
                    <p class="text-slate-600">{{ application.founder_name }} · {{ application.founder_email }}</p>
                </div>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" @click="rescreen">
                    Replay screening
                </button>
            </div>
        </header>

        <main class="mx-auto grid max-w-6xl gap-6 px-6 py-8 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-semibold">Application</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-slate-500">Stage</dt><dd>{{ application.stage }}</dd></div>
                    <div><dt class="text-slate-500">Sector</dt><dd>{{ application.sector || '—' }}</dd></div>
                    <div><dt class="text-slate-500">Status</dt><dd>{{ application.status }}</dd></div>
                </dl>
                <div v-if="application.form_data" class="mt-4 space-y-3 text-sm">
                    <div v-for="(value, key) in application.form_data" :key="key">
                        <p class="font-medium capitalize text-slate-500">{{ String(key).replace('_', ' ') }}</p>
                        <p class="text-slate-800">{{ value }}</p>
                    </div>
                </div>
            </section>

            <section v-if="application.screening" class="rounded-xl border border-indigo-200 bg-white p-6">
                <h2 class="font-semibold text-indigo-700">Gemini evaluation</h2>
                <p class="mt-2 text-5xl font-bold text-indigo-600">{{ application.screening.overall_score }}</p>
                <p class="mt-2 text-sm text-slate-500">Model: {{ application.screening.model }} · {{ application.screening.latency_ms }}ms · {{ application.screening.prompt_tokens + application.screening.completion_tokens }} tokens</p>
                <p class="mt-4 text-slate-700">{{ application.screening.summary }}</p>

                <div v-if="application.screening.strengths?.length" class="mt-4">
                    <p class="text-sm font-medium text-green-700">Strengths</p>
                    <ul class="mt-1 list-inside list-disc text-sm text-slate-600">
                        <li v-for="(s, i) in application.screening.strengths" :key="i">{{ s }}</li>
                    </ul>
                </div>

                <div v-if="application.screening.weaknesses?.length" class="mt-4">
                    <p class="text-sm font-medium text-amber-700">Weaknesses</p>
                    <ul class="mt-1 list-inside list-disc text-sm text-slate-600">
                        <li v-for="(w, i) in application.screening.weaknesses" :key="i">{{ w }}</li>
                    </ul>
                </div>

                <div v-if="application.screening.risk_flags?.length" class="mt-4">
                    <p class="text-sm font-medium text-red-700">Risk flags</p>
                    <ul class="mt-1 space-y-1 text-sm">
                        <li v-for="(r, i) in application.screening.risk_flags" :key="i" class="rounded bg-red-50 px-2 py-1">
                            [{{ r.severity }}] {{ r.message }}
                        </li>
                    </ul>
                </div>

                <details class="mt-6">
                    <summary class="cursor-pointer text-sm text-slate-500">Raw Gemini response</summary>
                    <pre class="mt-2 max-h-64 overflow-auto rounded bg-slate-900 p-3 text-xs text-green-400">{{ JSON.stringify(application.screening.raw_response, null, 2) }}</pre>
                </details>
            </section>

            <section v-else class="rounded-xl border border-slate-200 bg-white p-6">
                <p class="text-slate-500">Screening pending or failed. Click "Replay screening" to trigger Gemini.</p>
                <p v-if="application.screening?.error" class="mt-2 text-sm text-red-600">{{ application.screening.error }}</p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
                <h2 class="font-semibold">Agent execution trace</h2>
                <ol class="mt-4 space-y-2">
                    <li v-for="(step, i) in application.agent_executions" :key="i" class="flex gap-3 text-sm">
                        <span class="font-mono text-xs text-slate-400">{{ step.created_at }}</span>
                        <span class="font-medium">{{ step.step }}</span>
                        <span class="text-slate-600">{{ step.action_taken }}</span>
                    </li>
                </ol>
            </section>
        </main>
    </div>
</template>
