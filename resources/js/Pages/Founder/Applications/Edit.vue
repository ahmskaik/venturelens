<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import FounderShell from '../../../Components/Layout/FounderShell.vue';

const props = defineProps({
    application: Object,
    profile_options: Object,
});

const tab = ref('basic');
const form = ref({ ...props.application.form });
const pitchDeck = ref(null);
const logo = ref(null);
const submitting = ref(false);
const error = ref(null);

const tabs = [
    { key: 'basic', label: 'Basic' },
    { key: 'business', label: 'Business' },
    { key: 'funding', label: 'Funding' },
    { key: 'team', label: 'Team & story' },
];

function submit() {
    submitting.value = true;
    error.value = null;

    const data = new FormData();
    data.append('_method', 'PUT');
    Object.entries(form.value).forEach(([k, v]) => data.append(k, v ?? ''));
    if (pitchDeck.value) data.append('pitch_deck', pitchDeck.value);
    if (logo.value) data.append('logo', logo.value);

    router.post(`/founder/applications/${props.application.id}`, data, {
        forceFormData: true,
        onError: (errors) => {
            error.value = Object.values(errors).flat().join(' ');
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<template>
    <FounderShell
        :title="`Edit · ${form.startup_name || 'Project'}`"
        :subtitle="application.program.name"
        badge="Project profile"
    >
        <Link :href="`/founder/applications/${application.id}`" class="text-sm text-emerald-600 hover:underline">← Back to application</Link>

        <form class="mt-6" novalidate @submit.prevent="submit">
            <div class="flex flex-wrap gap-2 border-b border-slate-200">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    type="button"
                    class="border-b-2 px-4 py-3 text-sm font-semibold"
                    :class="tab === t.key ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-slate-500'"
                    @click="tab = t.key"
                >
                    {{ t.label }}
                </button>
            </div>

            <div class="vl-card mt-6 space-y-4 p-6">
                <div v-show="tab === 'basic'" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Project name</label>
                        <input v-model="form.startup_name" class="vl-input mt-1.5" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium">Short description</label>
                        <textarea v-model="form.short_description" rows="2" class="vl-input mt-1.5" />
                    </div>
                    <div><label class="text-sm font-medium">Founder name</label><input v-model="form.founder_name" class="vl-input mt-1.5" /></div>
                    <div><label class="text-sm font-medium">Email</label><input v-model="form.founder_email" disabled class="vl-input mt-1.5 bg-slate-50" /></div>
                    <div><label class="text-sm font-medium">Country</label><input v-model="form.country_code" maxlength="2" class="vl-input mt-1.5 uppercase" /></div>
                    <div><label class="text-sm font-medium">Website</label><input v-model="form.website" class="vl-input mt-1.5" /></div>
                    <div><label class="text-sm font-medium">Replace logo</label><input type="file" accept="image/*" class="mt-1.5 w-full text-sm" @change="logo = $event.target.files[0]" /></div>
                    <div><label class="text-sm font-medium">Replace pitch deck</label><input type="file" accept="application/pdf" class="mt-1.5 w-full text-sm" @change="pitchDeck = $event.target.files[0]" /></div>
                </div>

                <div v-show="tab === 'business'" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium">Sector</label>
                        <select v-model="form.sector" class="vl-input mt-1.5">
                            <option value="">Select</option>
                            <option v-for="(label, key) in profile_options.sectors" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Stage</label>
                        <select v-model="form.stage" class="vl-input mt-1.5">
                            <option v-for="(label, key) in profile_options.stages" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Problem</label><textarea v-model="form.problem" rows="3" class="vl-input mt-1.5" /></div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Solution</label><textarea v-model="form.solution" rows="3" class="vl-input mt-1.5" /></div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Traction</label><textarea v-model="form.traction" rows="2" class="vl-input mt-1.5" /></div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">How you make money</label><textarea v-model="form.business_model_summary" rows="3" class="vl-input mt-1.5" /></div>
                </div>

                <div v-show="tab === 'funding'" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Use of funds</label><textarea v-model="form.funds_use" rows="3" class="vl-input mt-1.5" /></div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Funding needs</label><input v-model="form.funding_needs" class="vl-input mt-1.5" /></div>
                </div>

                <div v-show="tab === 'team'" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Why applying?</label><textarea v-model="form.application_reason" rows="3" class="vl-input mt-1.5" /></div>
                    <div class="sm:col-span-2"><label class="text-sm font-medium">Your story</label><textarea v-model="form.story" rows="4" class="vl-input mt-1.5" /></div>
                </div>
            </div>

            <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
            <button type="submit" :disabled="submitting" class="vl-btn-primary mt-6 bg-emerald-600 hover:bg-emerald-700">
                {{ submitting ? 'Saving…' : 'Save project profile' }}
            </button>
        </form>
    </FounderShell>
</template>
