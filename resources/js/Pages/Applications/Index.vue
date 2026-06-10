<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    program: Object,
    applications: Array,
});
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4">
            <Link href="/dashboard" class="text-sm text-indigo-600 hover:underline">← Dashboard</Link>
            <h1 class="mt-2 text-2xl font-bold">{{ program.name }}</h1>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Startup</th>
                            <th class="px-4 py-3">Founder</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">AI Score</th>
                            <th class="px-4 py-3">Recommendation</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="app in applications" :key="app.id" class="border-t border-slate-100">
                            <td class="px-4 py-3 font-medium">{{ app.startup_name }}</td>
                            <td class="px-4 py-3">{{ app.founder_name }}</td>
                            <td class="px-4 py-3">{{ app.status }}</td>
                            <td class="px-4 py-3">{{ app.ai_overall_score ?? '—' }}</td>
                            <td class="px-4 py-3">{{ app.recommendation ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Link :href="`/applications/${app.id}`" class="text-indigo-600 hover:underline">Review</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
