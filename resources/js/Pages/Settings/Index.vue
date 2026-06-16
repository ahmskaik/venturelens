<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../Components/Layout/AppShell.vue';

const props = defineProps({
    profile: Object,
    organization: Object,
    can_manage_organization: Boolean,
    role: String,
});

const page = usePage();
const tab = ref('profile');

const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
    password: '',
    password_confirmation: '',
});

const orgForm = useForm({
    name: props.organization.name,
    country_code: props.organization.country_code,
    website: props.organization.website ?? '',
});

function saveProfile() {
    profileForm
        .transform((data) => ({
            ...data,
            password: data.password || undefined,
            password_confirmation: data.password ? data.password_confirmation : undefined,
        }))
        .put('/settings/profile', {
            preserveScroll: true,
            onSuccess: () => {
                profileForm.password = '';
                profileForm.password_confirmation = '';
            },
        });
}

function saveOrganization() {
    orgForm.put('/settings/organization', { preserveScroll: true });
}
</script>

<template>
    <AppShell
        title="Settings"
        subtitle="Manage your personal account and incubator profile."
        badge="Account"
    >
        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <div class="flex gap-2 border-b border-slate-200">
            <button
                type="button"
                class="border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'profile' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                @click="tab = 'profile'"
            >
                Personal profile
            </button>
            <button
                type="button"
                class="border-b-2 px-4 py-3 text-sm font-semibold transition"
                :class="tab === 'organization' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                @click="tab = 'organization'"
            >
                Organization
            </button>
        </div>

        <section v-show="tab === 'profile'" class="mt-8">
            <div class="vl-card-elevated max-w-xl p-8">
                <h2 class="vl-display text-lg font-bold text-slate-900">Your account</h2>
                <p class="mt-1 text-sm text-slate-500">Name and email used for login and notifications.</p>

                <form class="mt-6 space-y-4" @submit.prevent="saveProfile">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="profileForm.name" required class="vl-input mt-1.5" />
                        <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input v-model="profileForm.email" type="email" required class="vl-input mt-1.5" />
                        <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
                    </div>
                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-sm font-medium text-slate-700">Change password</p>
                        <p class="text-xs text-slate-500">Leave blank to keep your current password.</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium">New password</label>
                                <input v-model="profileForm.password" type="password" class="vl-input mt-1.5" autocomplete="new-password" />
                                <p v-if="profileForm.errors.password" class="mt-1 text-sm text-red-600">{{ profileForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Confirm password</label>
                                <input v-model="profileForm.password_confirmation" type="password" class="vl-input mt-1.5" autocomplete="new-password" />
                            </div>
                        </div>
                    </div>
                    <button type="submit" :disabled="profileForm.processing" class="vl-btn-primary">
                        Save profile
                    </button>
                </form>
            </div>
        </section>

        <section v-show="tab === 'organization'" class="mt-8">
            <div class="vl-card-elevated max-w-xl p-8">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="vl-display text-lg font-bold text-slate-900">Incubator profile</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Public-facing organization details shown to applicants.
                            <span v-if="role" class="capitalize">Your role: {{ role }}</span>
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold capitalize text-slate-600">
                        Plan: {{ organization.plan }}
                    </span>
                </div>

                <div v-if="!can_manage_organization" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Only organization owners and managers can edit these settings.
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="saveOrganization">
                    <div>
                        <label class="block text-sm font-medium">Organization name</label>
                        <input
                            v-model="orgForm.name"
                            required
                            :disabled="!can_manage_organization"
                            class="vl-input mt-1.5 disabled:cursor-not-allowed disabled:bg-slate-50"
                        />
                        <p v-if="orgForm.errors.name" class="mt-1 text-sm text-red-600">{{ orgForm.errors.name }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Country</label>
                            <input
                                v-model="orgForm.country_code"
                                maxlength="2"
                                required
                                :disabled="!can_manage_organization"
                                class="vl-input mt-1.5 uppercase disabled:cursor-not-allowed disabled:bg-slate-50"
                            />
                            <p v-if="orgForm.errors.country_code" class="mt-1 text-sm text-red-600">{{ orgForm.errors.country_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Slug</label>
                            <input :value="organization.slug" disabled class="vl-input mt-1.5 cursor-not-allowed bg-slate-50 text-slate-500" />
                            <p class="mt-1 text-xs text-slate-400">Internal identifier — does not change.</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Website</label>
                        <input
                            v-model="orgForm.website"
                            type="url"
                            placeholder="https://"
                            :disabled="!can_manage_organization"
                            class="vl-input mt-1.5 disabled:cursor-not-allowed disabled:bg-slate-50"
                        />
                        <p v-if="orgForm.errors.website" class="mt-1 text-sm text-red-600">{{ orgForm.errors.website }}</p>
                    </div>
                    <p class="text-sm text-slate-500">
                        Screenings: {{ organization.screenings_used }} / {{ organization.screenings_quota }} used —
                        <a href="/billing" class="font-medium text-brand-600 hover:text-brand-700">manage plan in Billing</a>
                    </p>
                    <button
                        v-if="can_manage_organization"
                        type="submit"
                        :disabled="orgForm.processing"
                        class="vl-btn-primary"
                    >
                        Save organization
                    </button>
                </form>
            </div>
        </section>
    </AppShell>
</template>
