<script setup>
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    agents: Array,
    executions: Array,
    growth_drafts: Array,
    support_requests: Array,
});

const form = useForm({
    subject: '',
    question: '',
});

const autonomyLabels = {
    0: 'L0 Observe',
    1: 'L1 Suggest',
    2: 'L2 Act w/ approval',
    3: 'L3 Autonomous',
};
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4">
            <Link href="/dashboard" class="text-sm text-indigo-600 hover:underline">← Dashboard</Link>
            <h1 class="mt-2 text-2xl font-bold">AI Operations</h1>
            <p class="text-sm text-slate-600">Autonomous agents running the VentureLens business</p>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8 space-y-8">
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm text-slate-500">Agent actions (platform)</p>
                    <p class="text-2xl font-bold">{{ stats.total_actions }}</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-sm text-indigo-600">Decisions by AI (L2–L3)</p>
                    <p class="text-2xl font-bold text-indigo-700">{{ stats.ai_decision_percent }}%</p>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-sm text-emerald-600">Human hours displaced</p>
                    <p class="text-2xl font-bold text-emerald-700">{{ stats.human_hours_displaced }}</p>
                </div>
                <Link href="/impact" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300">
                    <p class="text-sm text-slate-500">Public evidence</p>
                    <p class="text-lg font-bold text-indigo-600">View /impact →</p>
                </Link>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-semibold">Autonomy distribution</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="(count, level) in stats.autonomy_distribution" :key="level">
                        <div class="flex justify-between text-sm">
                            <span>{{ autonomyLabels[level] || `L${level}` }}</span>
                            <span class="text-slate-500">{{ count }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100">
                            <div
                                class="h-2 rounded-full bg-indigo-500"
                                :style="{ width: `${Math.round((count / Math.max(...Object.values(stats.autonomy_distribution), 1)) * 100)}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-slate-200 bg-white p-6">
                    <h2 class="font-semibold">Support Agent (A3)</h2>
                    <p class="mt-1 text-sm text-slate-600">Ask a question — Gemini Support Agent will answer or escalate.</p>
                    <form class="mt-4 space-y-3" @submit.prevent="form.post('/ai-operations/support')">
                        <input v-model="form.subject" required placeholder="Subject" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <textarea v-model="form.question" required rows="3" placeholder="Your question..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Submit to Support Agent
                        </button>
                    </form>
                    <div v-if="support_requests.length" class="mt-6 space-y-3">
                        <div v-for="req in support_requests" :key="req.id" class="rounded-lg border border-slate-100 p-3 text-sm">
                            <p class="font-medium">{{ req.subject }} <span class="text-slate-400">· {{ req.status }}</span></p>
                            <p v-if="req.ai_response" class="mt-1 text-slate-600">{{ req.ai_response }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6">
                    <h2 class="font-semibold">Growth Agent (A1) — outreach drafts</h2>
                    <p class="mt-1 text-sm text-slate-600">Drafts personalized outreach (L1 — human review before send).</p>
                    <div v-if="growth_drafts.length" class="mt-4 space-y-3">
                        <div v-for="draft in growth_drafts" :key="draft.id" class="rounded-lg border border-slate-100 p-3 text-sm">
                            <p class="font-medium">{{ draft.target_organization }}</p>
                            <p class="text-slate-500">{{ draft.subject }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ draft.status }} · L{{ draft.autonomy_level }}</p>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-400">No drafts yet. Run <code class="rounded bg-slate-100 px-1">php artisan agents:run-growth</code></p>
                </section>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-semibold">Agent registry</h2>
                <table class="mt-4 w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="pb-2">Agent</th>
                            <th class="pb-2">Enabled</th>
                            <th class="pb-2">Max autonomy</th>
                            <th class="pb-2">Daily cap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="agent in agents" :key="agent.name" class="border-t border-slate-100">
                            <td class="py-2 capitalize">{{ agent.name }}</td>
                            <td class="py-2">{{ agent.enabled ? 'Yes' : 'No' }}</td>
                            <td class="py-2">L{{ agent.autonomy_level }}</td>
                            <td class="py-2">{{ agent.daily_action_cap }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="font-semibold">Execution log</h2>
                <div class="mt-4 max-h-96 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-white text-left text-slate-500">
                            <tr>
                                <th class="pb-2">Time</th>
                                <th class="pb-2">Agent</th>
                                <th class="pb-2">Step</th>
                                <th class="pb-2">Decision</th>
                                <th class="pb-2">L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(e, i) in executions" :key="i" class="border-t border-slate-100">
                                <td class="py-2 font-mono text-xs">{{ e.created_at?.slice(11, 19) }}</td>
                                <td class="py-2 capitalize">{{ e.agent_name || '—' }}</td>
                                <td class="py-2 font-mono text-xs">{{ e.step }}</td>
                                <td class="py-2">{{ e.decision }}</td>
                                <td class="py-2">L{{ e.autonomy_level }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
