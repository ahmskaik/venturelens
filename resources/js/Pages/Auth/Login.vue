<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Logo from '../../Components/Brand/Logo.vue';
import GeminiBadge from '../../Components/Brand/GeminiBadge.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
</script>

<template>
    <div class="flex min-h-screen">
        <div class="hidden w-1/2 vl-gradient-bg lg:flex lg:flex-col lg:justify-between lg:p-12">
            <Logo dark />
            <div>
                <h2 class="vl-display text-3xl font-bold leading-tight text-white">
                    AI-native incubation,<br />powered by Gemini.
                </h2>
                <p class="mt-4 max-w-md text-slate-400">
                    Demo account for judges: <span class="font-mono text-violet-300">demo@venturelens.app</span>
                </p>
                <GeminiBadge size="lg" class="mt-6" />
            </div>
            <p class="text-xs text-slate-600">Build with Gemini XPRIZE 2026</p>
        </div>

        <div class="flex flex-1 items-center justify-center bg-slate-50 px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <Logo />
                </div>
                <div class="vl-card-elevated p-8">
                    <h1 class="vl-display text-2xl font-bold text-slate-900">Welcome back</h1>
                    <p class="mt-1 text-sm text-slate-600">Sign in to your incubator dashboard</p>

                    <form class="mt-8 space-y-5" @submit.prevent="form.post('/login')">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input v-model="form.email" type="email" required class="vl-input mt-1.5" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Password</label>
                            <input v-model="form.password" type="password" required class="vl-input mt-1.5" />
                        </div>
                        <button type="submit" :disabled="form.processing" class="vl-btn-primary w-full disabled:opacity-50">
                            Log in
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-600">
                        <Link href="/register" class="font-medium text-brand-600 hover:text-brand-700">Create an account</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
