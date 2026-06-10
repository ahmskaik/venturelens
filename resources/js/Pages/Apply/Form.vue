<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    program: Object,
});

const form = ref({
    startup_name: '',
    founder_name: '',
    founder_email: '',
    country_code: 'TR',
    stage: 'mvp',
    sector: '',
    one_liner: '',
    problem: '',
    solution: '',
    market: '',
    traction: '',
    team: '',
    funding_needs: '',
});
const pitchDeck = ref(null);
const submitting = ref(false);
const error = ref(null);

async function submit() {
    submitting.value = true;
    error.value = null;

    const data = new FormData();
    Object.entries(form.value).forEach(([k, v]) => data.append(k, v ?? ''));
    if (pitchDeck.value) {
        data.append('pitch_deck', pitchDeck.value);
    }

    try {
        router.post(`/apply/${props.program.slug}`, data, {
            forceFormData: true,
            onError: (errors) => {
                error.value = Object.values(errors).flat().join(' ');
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
    } catch (e) {
        error.value = e.message;
        submitting.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 py-10">
        <div class="mx-auto max-w-2xl px-4">
            <Link href="/" class="text-sm text-indigo-600 hover:underline">← VentureLens</Link>
            <h1 class="mt-4 text-3xl font-bold">{{ program.name }}</h1>
            <p class="mt-2 text-slate-600">{{ program.description }}</p>
            <p v-if="!program.accepting" class="mt-4 rounded-lg bg-amber-50 p-3 text-amber-800">Applications are currently closed.</p>

            <form v-else class="mt-8 space-y-4 rounded-xl border border-slate-200 bg-white p-6" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Startup name *</label>
                        <input v-model="form.startup_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Founder name *</label>
                        <input v-model="form.founder_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Founder email *</label>
                        <input v-model="form.founder_email" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Country *</label>
                        <input v-model="form.country_code" maxlength="2" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Stage *</label>
                        <select v-model="form.stage" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="idea">Idea</option>
                            <option value="mvp">MVP</option>
                            <option value="growth">Growth</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">One-liner</label>
                        <input v-model="form.one_liner" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Problem</label>
                        <textarea v-model="form.problem" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Solution</label>
                        <textarea v-model="form.solution" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Pitch deck (PDF, max 20MB)</label>
                        <input type="file" accept="application/pdf" class="mt-1 w-full text-sm" @change="pitchDeck = $event.target.files[0]" />
                    </div>
                </div>

                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

                <button type="submit" :disabled="submitting" class="w-full rounded-lg bg-indigo-600 py-3 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    {{ submitting ? 'Submitting…' : 'Submit — Gemini screening starts immediately' }}
                </button>
            </form>
        </div>
    </div>
</template>
