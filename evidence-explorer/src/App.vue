<script setup>
import { ref } from 'vue';
import { useImpactApi } from './composables/useImpactApi';
import { useTheme } from './composables/useTheme';
import DashboardView from './views/DashboardView.vue';
import AgentsView from './views/AgentsView.vue';

const tab = ref('dashboard');
const { data, loading, error, refresh, baseUrl } = useImpactApi();
const { isDark, toggle } = useTheme();

const tabs = [
    { id: 'dashboard', label: 'Live KPIs' },
    { id: 'agents', label: 'Agent explorer' },
];
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <a
                        :href="`${baseUrl}/impact`"
                        class="text-lg font-semibold text-slate-900 dark:text-white"
                    >
                        Venture<span class="text-brand-600">Lens</span>
                    </a>
                    <span class="rounded-full bg-brand-600/10 px-2 py-0.5 text-xs font-medium text-brand-600">
                        Evidence Explorer
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <nav class="flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
                        <button
                            v-for="t in tabs"
                            :key="t.id"
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                            :class="
                                tab === t.id
                                    ? 'bg-brand-600 text-white'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            "
                            @click="tab = t.id"
                        >
                            {{ t.label }}
                        </button>
                    </nav>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700"
                        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        @click="toggle"
                    >
                        {{ isDark ? '☀️' : '🌙' }}
                    </button>
                    <a
                        :href="`${baseUrl}/impact`"
                        class="hidden text-sm font-medium text-brand-600 hover:text-brand-700 sm:inline"
                    >
                        Full report →
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 space-y-8 px-4 py-8 sm:px-6">
            <div v-if="tab === 'dashboard'" class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Live impact dashboard</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Production metrics from <code class="text-xs">/api/v1/impact.json</code> · refreshes every 60s
                    </p>
                </div>
                <span
                    class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    Live from production
                </span>
            </div>

            <div v-else>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Agent execution explorer</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    AI-native operations — autonomy levels L0 through L3
                </p>
            </div>

            <DashboardView
                v-if="tab === 'dashboard'"
                :metrics="data"
                :loading="loading"
                :error="error"
                @retry="refresh"
            />
            <AgentsView
                v-else
                :recent-executions="data?.recent_agent_executions ?? []"
            />
        </main>

        <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            Evidence Explorer · Build with Gemini XPRIZE · Data from production API
        </footer>
    </div>
</template>
