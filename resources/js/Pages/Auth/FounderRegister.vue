<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Logo from '../../Components/Brand/Logo.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    default_country_code: 'TR',
});
</script>

<template>
    <div class="flex min-h-screen">
        <div class="hidden w-2/5 bg-gradient-to-br from-emerald-950 via-teal-950 to-slate-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <Logo dark />
            <div>
                <h2 class="vl-display text-3xl font-bold leading-tight text-white">
                    Manage your startup applications in one place.
                </h2>
                <p class="mt-4 text-slate-400">Track Gemini screening, update your project profile, and read program decisions.</p>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-center bg-slate-50 px-6 py-12">
            <div class="w-full max-w-lg">
                <div class="vl-card-elevated p-8">
                    <h1 class="vl-display text-2xl font-bold">Founder account</h1>
                    <p class="mt-1 text-sm text-slate-600">Free — link applications by email automatically.</p>

                    <form class="mt-8 space-y-4" @submit.prevent="form.post('/founder/register')">
                        <input v-model="form.name" required placeholder="Your name" class="vl-input" />
                        <input v-model="form.email" type="email" required placeholder="Email" class="vl-input" />
                        <input v-model="form.default_country_code" maxlength="2" required placeholder="Country (TR)" class="vl-input uppercase" />
                        <input v-model="form.password" type="password" required placeholder="Password" class="vl-input" />
                        <input v-model="form.password_confirmation" type="password" required placeholder="Confirm password" class="vl-input" />
                        <button type="submit" :disabled="form.processing" class="vl-btn-primary w-full bg-emerald-600 hover:bg-emerald-700">
                            Create founder account
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm">
                        <Link href="/login" class="text-emerald-600 hover:underline">Log in</Link>
                        ·
                        <Link href="/register" class="text-brand-600 hover:underline">Incubator signup</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
