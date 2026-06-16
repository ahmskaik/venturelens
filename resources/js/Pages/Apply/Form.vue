<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SeoHead from '../../Components/Seo/SeoHead.vue';
import { buildApplyJsonLd, seoDefaults } from '../../seo/defaults.js';

const props = defineProps({
    program: Object,
    profile_options: Object,
    prefill: Object,
    founder_logged_in: Boolean,
});

const defaultForm = {
    startup_name: '',
    founder_name: '',
    founder_email: '',
    country_code: 'TR',
    stage: 'seed',
    sector: '',
    short_description: '',
    website: '',
    pitch_deck_youtube_url: '',
    business_type: 'b2b',
    operating_status: 'active',
    legally_incorporated: 'no',
    business_model: 'saas',
    target_customers: '',
    primary_revenue_source: '',
    secondary_revenue_source: '',
    founding_year: '',
    business_model_summary: '',
    revenue_generating: 'no',
    received_funding: 'no',
    burn_rate_usd: '',
    runway_months: '',
    revenue_goal_usd: '',
    funds_use: '',
    funding_needs: '',
    co_founder_count: '1',
    team_member_count: '',
    application_reason: '',
    awards: '',
    story: '',
    problem: '',
    solution: '',
    market: '',
    traction: '',
    team: '',
};

const tab = ref('basic');
const page = usePage();

const applySeo = computed(() =>
    seoDefaults.apply(props.program.name, props.program.organization),
);
const applyJsonLd = computed(() => buildApplyJsonLd(page.props.seo?.appUrl ?? '', props.program));

const form = ref({
    ...defaultForm,
    ...(props.prefill ?? {}),
});

const pitchDeck = ref(null);
const logo = ref(null);
const submitting = ref(false);
const error = ref(null);

const tabs = [
    { key: 'basic', label: 'Basic information' },
    { key: 'business', label: 'Business information' },
    { key: 'funding', label: 'Funding' },
    { key: 'team', label: 'Team & story' },
];

const requiredFields = [
    { key: 'startup_name', tab: 'basic', label: 'Project name' },
    { key: 'founder_name', tab: 'basic', label: 'Founder name' },
    { key: 'founder_email', tab: 'basic', label: 'Founder email' },
    { key: 'country_code', tab: 'basic', label: 'Country' },
    { key: 'stage', tab: 'business', label: 'Project stage' },
];

function validateForm() {
    for (const field of requiredFields) {
        const value = String(form.value[field.key] ?? '').trim();
        if (!value) {
            tab.value = field.tab;
            error.value = `${field.label} is required.`;
            return false;
        }
    }

    if (form.value.founder_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.founder_email)) {
        tab.value = 'basic';
        error.value = 'Enter a valid founder email.';
        return false;
    }

    if (form.value.country_code && form.value.country_code.trim().length !== 2) {
        tab.value = 'basic';
        error.value = 'Country must be a 2-letter code (e.g. TR, US).';
        return false;
    }

    return true;
}

async function submit() {
    if (!validateForm()) {
        return;
    }

    submitting.value = true;
    error.value = null;

    const data = new FormData();
    Object.entries(form.value).forEach(([k, v]) => data.append(k, v ?? ''));
    if (pitchDeck.value) {
        data.append('pitch_deck', pitchDeck.value);
    }
    if (logo.value) {
        data.append('logo', logo.value);
    }

    router.post(`/apply/${props.program.slug}`, data, {
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
    <SeoHead
        :title="applySeo.title"
        :description="program.description || applySeo.description"
        :keywords="applySeo.keywords"
        :url="`/apply/${program.slug}`"
        :json-ld="applyJsonLd"
    />
    <div class="min-h-screen bg-slate-50 py-10">
        <div class="mx-auto max-w-3xl px-4">
            <Link href="/" class="text-sm font-medium text-brand-600 hover:text-brand-700">VentureLens</Link>
            <h1 class="mt-4 text-2xl font-semibold text-slate-900">{{ program.name }}</h1>
            <p class="mt-2 text-slate-600">{{ program.description }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ program.organization }}</p>
            <p v-if="founder_logged_in" class="mt-4 rounded-lg border border-slate-200 bg-slate-100 p-3 text-sm text-slate-700">
                Signed in as founder —
                <a href="/founder/dashboard" class="font-medium underline">open your portal</a>
            </p>
            <p v-if="!program.accepting" class="mt-4 rounded-xl bg-amber-50 p-4 text-amber-900">
                Applications are currently closed.
            </p>

            <form v-else class="mt-8" novalidate @submit.prevent="submit">
                <div class="flex flex-wrap gap-2 border-b border-slate-200">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="border-b-2 px-4 py-3 text-sm font-semibold transition"
                        :class="tab === t.key ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        @click="tab = t.key"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <div class="vl-card mt-6 p-6">
                    <!-- Basic -->
                    <div v-show="tab === 'basic'" class="space-y-4">
                        <h2 class="text-base font-semibold text-slate-900">Basic information</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Project name *</label>
                                <input v-model="form.startup_name" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Short description</label>
                                <textarea v-model="form.short_description" rows="2" class="vl-input mt-1.5" placeholder="One-line summary of your venture" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Founder name *</label>
                                <input v-model="form.founder_name" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Founder email *</label>
                                <input v-model="form.founder_email" type="email" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Country *</label>
                                <input v-model="form.country_code" maxlength="2" class="vl-input mt-1.5 uppercase" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Website</label>
                                <input v-model="form.website" type="text" inputmode="url" placeholder="https://" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">YouTube link of your pitch deck</label>
                                <input v-model="form.pitch_deck_youtube_url" type="text" inputmode="url" placeholder="https://youtube.com/..." class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Project logo</label>
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="mt-1.5 w-full text-sm" @change="logo = $event.target.files[0]" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Pitch deck (PDF, max 20MB)</label>
                                <input type="file" accept="application/pdf" class="mt-1.5 w-full text-sm" @change="pitchDeck = $event.target.files[0]" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Type</label>
                                <select v-model="form.business_type" class="vl-input mt-1.5">
                                    <option v-for="(label, key) in profile_options.business_types" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Operating status</label>
                                <select v-model="form.operating_status" class="vl-input mt-1.5">
                                    <option v-for="(label, key) in profile_options.operating_statuses" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Are you legally incorporated?</label>
                                <div class="mt-2 flex gap-4">
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.legally_incorporated" type="radio" value="yes" /> Yes</label>
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.legally_incorporated" type="radio" value="no" /> No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business -->
                    <div v-show="tab === 'business'" class="space-y-4">
                        <h2 class="text-base font-semibold text-slate-900">Business information</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium">Sector / industry</label>
                                <select v-model="form.sector" class="vl-input mt-1.5">
                                    <option value="">Select sector</option>
                                    <option v-for="(label, key) in profile_options.sectors" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Business model</label>
                                <select v-model="form.business_model" class="vl-input mt-1.5">
                                    <option v-for="(label, key) in profile_options.business_models" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Project stage *</label>
                                <select v-model="form.stage" class="vl-input mt-1.5">
                                    <option v-for="(label, key) in profile_options.stages" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Founding year</label>
                                <input v-model="form.founding_year" type="number" min="1900" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Target customers</label>
                                <textarea v-model="form.target_customers" rows="2" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Primary revenue source</label>
                                <input v-model="form.primary_revenue_source" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Secondary revenue source</label>
                                <input v-model="form.secondary_revenue_source" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">How do you make money?</label>
                                <textarea v-model="form.business_model_summary" rows="3" class="vl-input mt-1.5" placeholder="Summarize your business model in a few sentences." />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Problem</label>
                                <textarea v-model="form.problem" rows="3" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Solution</label>
                                <textarea v-model="form.solution" rows="3" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Market</label>
                                <textarea v-model="form.market" rows="2" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Traction</label>
                                <textarea v-model="form.traction" rows="2" class="vl-input mt-1.5" />
                            </div>
                        </div>
                    </div>

                    <!-- Funding -->
                    <div v-show="tab === 'funding'" class="space-y-4">
                        <h2 class="text-base font-semibold text-slate-900">Funding</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium">Are you revenue generating?</label>
                                <div class="mt-2 flex gap-4">
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.revenue_generating" type="radio" value="yes" /> Yes</label>
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.revenue_generating" type="radio" value="no" /> No</label>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Received funding to date?</label>
                                <div class="mt-2 flex gap-4">
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.received_funding" type="radio" value="yes" /> Yes</label>
                                    <label class="flex items-center gap-2 text-sm"><input v-model="form.received_funding" type="radio" value="no" /> No</label>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium">Burn rate (USD / month)</label>
                                <input v-model="form.burn_rate_usd" type="number" min="0" step="100" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Runway (months)</label>
                                <input v-model="form.runway_months" type="number" min="0" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Revenue goal (USD)</label>
                                <input v-model="form.revenue_goal_usd" type="number" min="0" step="1000" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">What will the funds be used for?</label>
                                <textarea v-model="form.funds_use" rows="3" class="vl-input mt-1.5" placeholder="e.g. product development, hiring, marketing" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Funding needs (summary)</label>
                                <input v-model="form.funding_needs" class="vl-input mt-1.5" placeholder="e.g. $150K pre-seed" />
                            </div>
                        </div>
                    </div>

                    <!-- Team -->
                    <div v-show="tab === 'team'" class="space-y-4">
                        <h2 class="text-base font-semibold text-slate-900">Team & project story</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium">Co-founders</label>
                                <input v-model="form.co_founder_count" type="number" min="0" class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="text-sm font-medium">Team members (besides co-founders)</label>
                                <input v-model="form.team_member_count" type="number" min="0" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Why are you applying for this program?</label>
                                <textarea v-model="form.application_reason" rows="3" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Awards & recognition</label>
                                <textarea v-model="form.awards" rows="2" class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Your story</label>
                                <textarea v-model="form.story" rows="4" class="vl-input mt-1.5" placeholder="Share your journey, challenges, and vision." />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium">Team overview</label>
                                <textarea v-model="form.team" rows="3" class="vl-input mt-1.5" />
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm text-slate-500">Gemini screening starts immediately after submit.</p>
                    <button type="submit" :disabled="submitting" class="vl-btn-primary px-8">
                        {{ submitting ? 'Submitting…' : 'Submit application' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
