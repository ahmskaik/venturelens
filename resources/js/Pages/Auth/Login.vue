<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Logo from '../../Components/Brand/Logo.vue';
import SeoHead from '../../Components/Seo/SeoHead.vue';
import { seoDefaults } from '../../seo/defaults.js';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
</script>

<template>
    <SeoHead
        :title="seoDefaults.login.title"
        :description="seoDefaults.login.description"
        :keywords="seoDefaults.login.keywords"
        url="/login"
    />
    <div class="flex min-h-screen">
        <div class="hidden w-2/5 flex-col justify-between border-r border-slate-200 bg-slate-50 lg:flex lg:p-12">
            <Logo />
            <div>
                <h2 class="text-2xl font-semibold leading-snug text-slate-900">
                    Application screening for incubators and accelerators.
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-slate-600">
                    Structured AI evaluation, committee workflows, and founder communications — in one platform.
                </p>
            </div>
            <p class="text-xs text-slate-400">Demo: demo@venturelens.app</p>
        </div>

        <div class="flex flex-1 items-center justify-center bg-white px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <Logo />
                </div>
                <div class="vl-card p-8">
                    <h1 class="text-xl font-semibold text-slate-900">Sign in</h1>
                    <p class="mt-1 text-sm text-slate-600">Incubator or founder account</p>

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
                        <button type="submit" :disabled="form.processing" class="vl-btn-primary w-full">
                            Sign in
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-600">
                        <Link href="/register" class="font-medium text-brand-600 hover:text-brand-700">Create incubator account</Link>
                        <span class="mx-2 text-slate-300">·</span>
                        <Link href="/founder/register" class="font-medium text-slate-700 hover:text-slate-900">Founder portal</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
