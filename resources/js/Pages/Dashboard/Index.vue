<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    organization: Object,
    stats: Object,
    programs: Array,
    recent_executions: Array,
});
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <span class="text-xl font-bold text-indigo-600">VentureLens</span>
                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/dashboard" class="text-slate-600 hover:text-indigo-600">Dashboard</Link>
                    <Link href="/billing" class="text-slate-600 hover:text-indigo-600">Billing</Link>
                    <Link href="/ai-operations" class="text-slate-600 hover:text-indigo-600">AI Operations</Link>
                    <Link href="/impact" class="text-slate-600 hover:text-indigo-600">Impact</Link>
                    <Link href="/logout" method="post" as="button" class="text-slate-600 hover:text-indigo-600">Log out</Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            <h1 class="text-2xl font-bold">{{ organization.name }}</h1>
            <p class="text-sm text-slate-600">
                Plan: {{ organization.plan }} · Screenings {{ organization.screenings_used }}/{{ organization.screenings_quota }}
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-slate-500">Applications today</p>
                    <p class="text-2xl font-bold">{{ stats.applications_today }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-slate-500">Screenings completed</p>
                    <p class="text-2xl font-bold">{{ stats.screenings_completed }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-slate-500">Avg AI score</p>
                    <p class="text-2xl font-bold">{{ stats.avg_score ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-slate-500">Gemini calls (30d)</p>
                    <p class="text-2xl font-bold">{{ stats.gemini_calls_30d }}</p>
                    <p class="text-xs text-slate-400">{{ stats.gemini_tokens_30d }} tokens</p>
                </div>
            </div>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">Programs</h2>
                <div class="mt-4 space-y-2">
                    <div v-for="program in programs" :key="program.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <p class="font-medium">{{ program.name }}</p>
                            <p class="text-sm text-slate-500">{{ program.applications_count }} applications · {{ program.status }}</p>
                        </div>
                        <Link :href="`/programs/${program.id}/applications`" class="text-sm text-indigo-600 hover:underline">View applications</Link>
                    </div>
                </div>
            </section>

            <section class="mt-10">
                <h2 class="text-lg font-semibold">Agent execution log (screening pipeline)</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-4 py-2">Step</th>
                                <th class="px-4 py-2">Decision</th>
                                <th class="px-4 py-2">Action</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(exec, i) in recent_executions" :key="i" class="border-t border-slate-100">
                                <td class="px-4 py-2 font-mono text-xs">{{ exec.step }}</td>
                                <td class="px-4 py-2">{{ exec.decision }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ exec.action_taken }}</td>
                                <td class="px-4 py-2">{{ exec.status }}</td>
                            </tr>
                            <tr v-if="!recent_executions.length">
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">No executions yet — submit an application to see the pipeline.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
