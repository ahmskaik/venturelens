<script setup>
import { computed } from 'vue';

const props = defineProps({
    executions: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const steps = computed(() => props.executions ?? []);

function formatStep(step) {
    if (!step) return '';
    return step
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function formatTime(iso) {
    if (!iso) return '—';
    return iso.slice(11, 19);
}

function statusClass(status) {
    const map = {
        completed: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        failed: 'bg-red-100 text-red-800 ring-red-200',
        processing: 'bg-blue-100 text-blue-800 ring-blue-200',
    };
    return map[status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}
</script>

<template>
    <div v-if="steps.length" :class="compact ? 'space-y-0' : 'mt-6'">
        <ol :class="compact ? 'divide-y divide-slate-100' : 'space-y-0'">
            <li
                v-for="(step, i) in steps"
                :key="i"
                class="relative flex gap-3"
                :class="compact ? 'py-3' : (i < steps.length - 1 ? 'pb-6' : '')"
            >
                <div v-if="!compact" class="relative flex w-5 shrink-0 flex-col items-center">
                    <span
                        class="relative z-10 mt-1.5 h-3.5 w-3.5 rounded-full border-[3px] border-white bg-brand-500 shadow-sm ring-2 ring-brand-100"
                        aria-hidden="true"
                    />
                    <span
                        v-if="i < steps.length - 1"
                        class="absolute top-5 bottom-0 w-0.5 bg-gradient-to-b from-brand-300 via-brand-200 to-slate-200"
                        aria-hidden="true"
                    />
                </div>

                <div
                    class="min-w-0 flex-1"
                    :class="compact
                        ? ''
                        : 'rounded-xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white p-4 shadow-sm'"
                >
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <h3 :class="compact ? 'text-sm font-medium text-slate-900' : 'font-semibold text-slate-900'">
                            {{ formatStep(step.step) }}
                        </h3>
                        <time class="shrink-0 font-mono text-xs tabular-nums text-slate-400">
                            {{ formatTime(step.created_at) }}
                        </time>
                    </div>

                    <p :class="compact ? 'mt-1 text-xs leading-relaxed text-slate-600' : 'mt-2 text-sm leading-relaxed text-slate-600'">
                        {{ step.action_taken }}
                    </p>

                    <div v-if="step.decision || step.status" class="mt-2 flex flex-wrap gap-1.5">
                        <span
                            v-if="step.decision"
                            class="inline-flex items-center rounded bg-brand-50 px-1.5 py-0.5 font-mono text-[11px] text-brand-800 ring-1 ring-inset ring-brand-200"
                        >
                            {{ step.decision }}
                        </span>
                        <span
                            v-if="step.status"
                            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[11px] font-medium capitalize ring-1 ring-inset"
                            :class="statusClass(step.status)"
                        >
                            {{ step.status }}
                        </span>
                    </div>
                </div>
            </li>
        </ol>
    </div>

    <p v-else class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-400">
        No agent trace yet — run screening to populate this log.
    </p>
</template>
