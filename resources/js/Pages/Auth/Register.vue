<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Logo from '../../Components/Brand/Logo.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    organization_name: '',
    country_code: 'US',
});
</script>

<template>
    <div class="flex min-h-screen">
        <div class="hidden w-2/5 vl-gradient-bg lg:flex lg:flex-col lg:justify-between lg:p-12">
            <Logo dark />
            <div>
                <h2 class="vl-display text-3xl font-bold leading-tight text-white">
                    Start screening startups<br />with Gemini today.
                </h2>
                <p class="mt-4 text-slate-400">Free trial includes 5 Gemini screenings.</p>
                <GeminiBadge size="lg" class="mt-6" />
            </div>
            <p class="text-xs text-slate-600">Entrepreneurship & Job Creation</p>
        </div>

        <div class="flex flex-1 items-center justify-center bg-slate-50 px-6 py-12">
            <div class="w-full max-w-lg">
                <div class="mb-8 lg:hidden">
                    <Logo />
                </div>
                <div class="vl-card-elevated p-8">
                    <h1 class="vl-display text-2xl font-bold">Create your organization</h1>
                    <p class="mt-1 text-sm text-slate-600">Join incubators using AI-native application screening.</p>

                    <form class="mt-8 space-y-4" @submit.prevent="form.post('/register')">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Your name</label>
                                <input v-model="form.name" required class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Email</label>
                                <input v-model="form.email" type="email" required class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Password</label>
                                <input v-model="form.password" type="password" required class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Confirm</label>
                                <input v-model="form.password_confirmation" type="password" required class="vl-input mt-1.5" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Organization name</label>
                                <input v-model="form.organization_name" required class="vl-input mt-1.5" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Country</label>
                                <input v-model="form.country_code" maxlength="2" required class="vl-input mt-1.5 uppercase" />
                            </div>
                        </div>
                        <button type="submit" :disabled="form.processing" class="vl-btn-primary w-full">
                            Get started free
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm">
                        <Link href="/login" class="font-medium text-brand-600 hover:text-brand-700">Already have an account?</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
