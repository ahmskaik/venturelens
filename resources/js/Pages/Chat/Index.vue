<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';

const props = defineProps({
    session: Object,
    messages: Array,
    programs: Array,
});

const threadRef = ref(null);
const scope = ref(props.session?.program_id ? String(props.session.program_id) : '');

const form = useForm({
    message: '',
    program_id: props.session?.program_id ?? null,
});

const scopeLabel = computed(() => {
    if (!scope.value) return 'All programs';
    const program = props.programs.find((p) => p.id === Number(scope.value));
    return program?.name ?? 'Selected cohort';
});

watch(scope, (value) => {
    form.program_id = value ? Number(value) : null;
});

function scrollToBottom() {
    nextTick(() => {
        if (threadRef.value) {
            threadRef.value.scrollTop = threadRef.value.scrollHeight;
        }
    });
}

onMounted(scrollToBottom);
watch(() => props.messages, scrollToBottom, { deep: true });

function submit() {
    form.post('/ask', {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
    });
}

function clearChat() {
    if (!confirm('Clear this conversation?')) return;
    router.post('/ask/clear');
}

const suggestions = computed(() => {
    if (scope.value) {
        return [
            'Summarize applications in this cohort',
            'Which startups scored highest?',
            'Any risk flags I should review?',
        ];
    }
    return [
        'What does the AI screening score mean?',
        'How do I share a cohort apply link?',
        'Compare top-scoring startups across programs',
    ];
});
</script>

<template>
    <AppShell home breadcrumb="Ask">
        <div class="mx-auto flex h-[calc(100vh-7rem)] max-w-4xl flex-col">
            <!-- Toolbar -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900">Ask VentureLens</h1>
                    <p class="text-sm text-slate-500">RAG chat over your cohort data and platform docs</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="chat-scope">Knowledge scope</label>
                    <select
                        id="chat-scope"
                        v-model="scope"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                    >
                        <option value="">All programs</option>
                        <option v-for="p in programs" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.applications_count }})
                        </option>
                    </select>
                    <button type="button" class="vl-btn-ghost text-sm text-slate-500" @click="clearChat">
                        Clear
                    </button>
                </div>
            </div>

            <p class="mb-3 text-xs text-slate-400">
                Searching: <span class="font-medium text-slate-600">{{ scopeLabel }}</span>
            </p>

            <!-- Thread -->
            <div ref="threadRef" class="flex-1 space-y-5 overflow-y-auto pr-1">
                <div v-if="!messages.length" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 py-16 text-center">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-sm">
                        <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 font-semibold text-slate-900">Ask about your portfolio</h2>
                    <p class="mt-1 max-w-md text-sm text-slate-500">
                        Questions are answered from live application data, screening results, and platform documentation.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-2">
                        <button
                            v-for="s in suggestions"
                            :key="s"
                            type="button"
                            class="vl-pill-btn text-xs"
                            @click="form.message = s"
                        >
                            {{ s }}
                        </button>
                    </div>
                </div>

                <template v-for="msg in messages" :key="msg.id">
                    <div v-if="msg.role === 'user'" class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-md bg-slate-900 px-4 py-2.5 text-sm leading-relaxed text-white">
                            {{ msg.content }}
                        </div>
                    </div>
                    <div v-else class="flex justify-start">
                        <div class="max-w-[90%]">
                            <div class="rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm leading-relaxed text-slate-700 shadow-sm">
                                <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                            </div>
                            <div v-if="msg.sources?.length" class="mt-2 flex flex-wrap gap-1.5">
                                <Link
                                    v-for="src in msg.sources"
                                    :key="src.id"
                                    :href="src.url || '#'"
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                                    :class="{ 'pointer-events-none': !src.url }"
                                >
                                    {{ src.title }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="form.processing" class="flex justify-start">
                    <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-500">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Retrieving context…
                    </div>
                </div>
            </div>

            <!-- Composer -->
            <form class="mt-4 border-t border-slate-100 pt-4" @submit.prevent="submit">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm focus-within:border-slate-300 focus-within:ring-2 focus-within:ring-brand-500/10">
                    <textarea
                        v-model="form.message"
                        rows="3"
                        required
                        :placeholder="scope ? `Ask about ${scopeLabel}…` : 'Ask about screening, applications, or cohorts…'"
                        class="w-full resize-none border-0 bg-transparent px-4 py-3 text-sm focus:outline-none focus:ring-0"
                        @keydown.enter.exact.prevent="submit"
                    />
                    <div class="flex items-center justify-between border-t border-slate-100 px-3 py-2">
                        <p class="text-xs text-slate-400">Gemini RAG · {{ scopeLabel }}</p>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.message.trim()"
                            class="vl-btn-primary px-5 py-1.5 text-sm disabled:opacity-40"
                        >
                            Send
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
            </form>
        </div>
    </AppShell>
</template>
