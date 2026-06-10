<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatCard from '../../Components/Ui/StatCard.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

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
    <AppShell
        title="AI Operations"
        subtitle="Six autonomous agents run screening, growth, support, finance, onboarding, and success — in production."
        badge="AI-native business"
    >
        <template #actions>
            <GeminiBadge />
        </template>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Agent actions (platform)" :value="stats.total_actions" />
            <StatCard label="Decisions by AI (L2–L3)" :value="`${stats.ai_decision_percent}%`" variant="brand" />
            <StatCard label="Human hours displaced" :value="stats.human_hours_displaced" variant="success" />
            <Link href="/impact" class="vl-card flex flex-col justify-center p-5 transition hover:border-brand-300 hover:shadow-card-lg">
                <p class="text-sm text-slate-500">Public evidence</p>
                <p class="vl-display mt-2 text-xl font-bold text-brand-700">View /impact →</p>
            </Link>
        </div>

        <section class="vl-card mt-8 p-6">
            <h2 class="vl-display font-bold">Autonomy distribution</h2>
            <div class="mt-6 space-y-4">
                <div v-for="(count, level) in stats.autonomy_distribution" :key="level">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ autonomyLabels[level] || `L${level}` }}</span>
                        <span class="text-slate-500">{{ count }}</span>
                    </div>
                    <div class="mt-2 h-2.5 rounded-full bg-slate-100">
                        <div
                            class="h-2.5 rounded-full bg-gradient-to-r from-brand-500 to-violet-600"
                            :style="{ width: `${Math.round((count / Math.max(...Object.values(stats.autonomy_distribution), 1)) * 100)}%` }"
                        />
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Support Agent (A3)</h2>
                <p class="mt-1 text-sm text-slate-600">Ask a question — Gemini answers or escalates.</p>
                <form class="mt-5 space-y-3" @submit.prevent="form.post('/ai-operations/support')">
                    <input v-model="form.subject" required placeholder="Subject" class="vl-input" />
                    <textarea v-model="form.question" required rows="3" placeholder="Your question..." class="vl-input" />
                    <button type="submit" :disabled="form.processing" class="vl-btn-primary disabled:opacity-50">
                        Submit to Support Agent
                    </button>
                </form>
                <div v-if="support_requests.length" class="mt-6 space-y-3">
                    <div v-for="req in support_requests" :key="req.id" class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm">
                        <p class="font-semibold">{{ req.subject }} <span class="font-normal text-slate-400">· {{ req.status }}</span></p>
                        <p v-if="req.ai_response" class="mt-2 text-slate-600">{{ req.ai_response }}</p>
                    </div>
                </div>
            </section>

            <section class="vl-card p-6">
                <h2 class="vl-display font-bold">Growth Agent (A1)</h2>
                <p class="mt-1 text-sm text-slate-600">Personalized outreach drafts (L1 — human review before send).</p>
                <div v-if="growth_drafts.length" class="mt-5 space-y-3">
                    <div v-for="draft in growth_drafts" :key="draft.id" class="rounded-xl border border-brand-100 bg-brand-50/50 p-4 text-sm">
                        <p class="font-semibold text-brand-900">{{ draft.target_organization }}</p>
                        <p class="text-slate-600">{{ draft.subject }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ draft.status }} · L{{ draft.autonomy_level }}</p>
                    </div>
                </div>
                <p v-else class="mt-5 text-sm text-slate-400">
                    No drafts yet. Run <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">php artisan agents:run-growth</code>
                </p>
            </section>
        </div>

        <section class="vl-card mt-8 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="vl-display font-bold">Agent registry</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Agent</th>
                        <th class="px-6 py-3">Enabled</th>
                        <th class="px-6 py-3">Max autonomy</th>
                        <th class="px-6 py-3">Daily cap</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="agent in agents" :key="agent.name" class="border-t border-slate-100">
                        <td class="px-6 py-3 capitalize font-medium">{{ agent.name }}</td>
                        <td class="px-6 py-3">
                            <span :class="agent.enabled ? 'text-emerald-700' : 'text-slate-400'">{{ agent.enabled ? 'Yes' : 'No' }}</span>
                        </td>
                        <td class="px-6 py-3">L{{ agent.autonomy_level }}</td>
                        <td class="px-6 py-3">{{ agent.daily_action_cap }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="vl-card mt-8 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="vl-display font-bold">Execution log</h2>
            </div>
            <div class="max-h-96 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Agent</th>
                            <th class="px-6 py-3">Step</th>
                            <th class="px-6 py-3">Decision</th>
                            <th class="px-6 py-3">L</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(e, i) in executions" :key="i" class="border-t border-slate-100 hover:bg-brand-50/20">
                            <td class="px-6 py-2 font-mono text-xs">{{ e.created_at?.slice(11, 19) }}</td>
                            <td class="px-6 py-2 capitalize">{{ e.agent_name || '—' }}</td>
                            <td class="px-6 py-2 font-mono text-xs text-brand-700">{{ e.step }}</td>
                            <td class="px-6 py-2">{{ e.decision }}</td>
                            <td class="px-6 py-2">L{{ e.autonomy_level }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppShell>
</template>
