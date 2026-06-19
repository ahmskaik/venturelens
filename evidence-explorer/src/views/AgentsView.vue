<script setup>
import { ref, computed, onMounted } from 'vue';
import AgentBadge from '../components/AgentBadge.vue';
import AutonomyBadge from '../components/AutonomyBadge.vue';
import {
    AGENT_NAMES,
    AUTONOMY_LABELS,
    mergeExecutions,
    formatTimestamp,
    formatRelative,
} from '../utils/format';

const props = defineProps({
    recentExecutions: { type: Array, default: () => [] },
});

const sample = ref([]);
const selectedAgents = ref([...AGENT_NAMES]);
const selectedLevels = ref([0, 1, 2, 3]);

onMounted(async () => {
    try {
        const base = import.meta.env.BASE_URL;
        const res = await fetch(`${base}sample-agent-executions.json`);
        if (res.ok) {
            sample.value = await res.json();
        }
    } catch {
        sample.value = [];
    }
});

const allRows = computed(() => mergeExecutions(sample.value, props.recentExecutions));

const filtered = computed(() =>
    allRows.value.filter(
        (row) =>
            selectedAgents.value.includes(row.agent_name) &&
            selectedLevels.value.includes(Number(row.autonomy_level ?? 0)),
    ),
);

function toggleAgent(name) {
    if (selectedAgents.value.includes(name)) {
        selectedAgents.value = selectedAgents.value.filter((a) => a !== name);
    } else {
        selectedAgents.value = [...selectedAgents.value, name];
    }
}

function toggleLevel(level) {
    if (selectedLevels.value.includes(level)) {
        selectedLevels.value = selectedLevels.value.filter((l) => l !== level);
    } else {
        selectedLevels.value = [...selectedLevels.value, level].sort();
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="vl-card p-4">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Autonomy levels</h2>
            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-600 dark:text-slate-400">
                <span v-for="(label, level) in AUTONOMY_LABELS" :key="level">
                    <AutonomyBadge :level="Number(level)" /> {{ label.replace(/^L\d+\s/, '') }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <div>
                <p class="mb-2 text-xs font-medium uppercase text-slate-500">Filter by agent</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="name in AGENT_NAMES"
                        :key="name"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium capitalize transition"
                        :class="
                            selectedAgents.includes(name)
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'
                        "
                        @click="toggleAgent(name)"
                    >
                        {{ name }}
                    </button>
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs font-medium uppercase text-slate-500">Filter by autonomy</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="level in [0, 1, 2, 3]"
                        :key="level"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium transition"
                        :class="
                            selectedLevels.includes(level)
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400'
                        "
                        @click="toggleLevel(level)"
                    >
                        L{{ level }}
                    </button>
                </div>
            </div>
        </div>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            Showing {{ filtered.length }} of {{ allRows.length }} executions (sample + live API)
        </p>

        <ol class="relative space-y-0 border-l border-slate-200 dark:border-slate-800">
            <li
                v-for="row in filtered"
                :key="row.created_at + row.agent_name + row.step"
                class="relative ml-6 pb-8 last:pb-0"
            >
                <span
                    class="absolute -left-[1.65rem] mt-1.5 flex h-3 w-3 rounded-full border-2 border-white bg-brand-600 dark:border-slate-950"
                />
                <div class="vl-card p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <AgentBadge :name="row.agent_name" />
                        <AutonomyBadge :level="Number(row.autonomy_level ?? 0)" />
                        <span v-if="row.status" class="text-xs text-slate-400">{{ row.status }}</span>
                        <span class="ml-auto text-xs text-slate-500" :title="formatTimestamp(row.created_at)">
                            {{ formatRelative(row.created_at) }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                        <span class="text-slate-500 dark:text-slate-400">{{ row.step }}</span>
                        · {{ row.decision }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ row.action_taken }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ formatTimestamp(row.created_at) }}</p>
                </div>
            </li>
        </ol>

        <p v-if="filtered.length === 0" class="py-8 text-center text-sm text-slate-500">
            No executions match the current filters.
        </p>
    </div>
</template>
