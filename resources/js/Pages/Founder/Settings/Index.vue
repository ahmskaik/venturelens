<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import FounderShell from '../../../Components/Layout/FounderShell.vue';

const props = defineProps({
    profile: Object,
});

const page = usePage();

const form = useForm({
    name: props.profile.name,
    email: props.profile.email,
    password: '',
    password_confirmation: '',
    default_country_code: props.profile.default_country_code,
    phone: props.profile.phone ?? '',
    linkedin_url: props.profile.linkedin_url ?? '',
    bio: props.profile.bio ?? '',
});
</script>

<template>
    <FounderShell title="Your profile" subtitle="Account details used across applications." badge="Settings">
        <div v-if="page.props.flash?.success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <form class="vl-card-elevated max-w-xl space-y-4 p-8" @submit.prevent="form.put('/founder/settings')">
            <div>
                <label class="block text-sm font-medium">Name</label>
                <input v-model="form.name" required class="vl-input mt-1.5" />
            </div>
            <div>
                <label class="block text-sm font-medium">Email</label>
                <input v-model="form.email" type="email" required class="vl-input mt-1.5" />
            </div>
            <div>
                <label class="block text-sm font-medium">Default country</label>
                <input v-model="form.default_country_code" maxlength="2" class="vl-input mt-1.5 uppercase" />
            </div>
            <div>
                <label class="block text-sm font-medium">Phone</label>
                <input v-model="form.phone" class="vl-input mt-1.5" />
            </div>
            <div>
                <label class="block text-sm font-medium">LinkedIn</label>
                <input v-model="form.linkedin_url" class="vl-input mt-1.5" placeholder="https://linkedin.com/in/..." />
            </div>
            <div>
                <label class="block text-sm font-medium">Bio</label>
                <textarea v-model="form.bio" rows="3" class="vl-input mt-1.5" />
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-sm font-medium">Change password</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <input v-model="form.password" type="password" placeholder="New password" class="vl-input" />
                    <input v-model="form.password_confirmation" type="password" placeholder="Confirm" class="vl-input" />
                </div>
            </div>
            <button type="submit" :disabled="form.processing" class="vl-btn-primary bg-emerald-600 hover:bg-emerald-700">Save profile</button>
        </form>
    </FounderShell>
</template>
