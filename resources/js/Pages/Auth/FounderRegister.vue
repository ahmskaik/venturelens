<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Logo from '../../Components/Brand/Logo.vue';
import SeoHead from '../../Components/Seo/SeoHead.vue';
import { seoDefaults } from '../../seo/defaults.js';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    default_country_code: 'TR',
});
</script>

<template>
    <SeoHead
        :title="seoDefaults.founderRegister.title"
        :description="seoDefaults.founderRegister.description"
        :keywords="seoDefaults.founderRegister.keywords"
        url="/founder/register"
    />
    <div class="flex min-h-screen">
        <div class="hidden w-2/5 flex-col justify-between border-r border-slate-200 bg-slate-50 lg:flex lg:p-12">
            <Logo />
            <div>
                <h2 class="text-2xl font-semibold leading-snug text-slate-900">
                    Manage your startup applications in one place.
                </h2>
                <p class="mt-4 text-sm text-slate-600">Track screening results, update your profile, and read program decisions.</p>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-center bg-white px-6 py-12">
            <div class="w-full max-w-lg">
                <div class="mb-8 lg:hidden">
                    <Logo />
                </div>
                <div class="vl-card p-8">
                    <h1 class="text-xl font-semibold text-slate-900">Create founder account</h1>
                    <p class="mt-1 text-sm text-slate-600">Free — applications link automatically by email.</p>

                    <form class="mt-8 space-y-4" @submit.prevent="form.post('/founder/register')">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Your name</label>
                            <input v-model="form.name" required class="vl-input mt-1.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input v-model="form.email" type="email" required class="vl-input mt-1.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Country</label>
                            <input v-model="form.default_country_code" maxlength="2" required class="vl-input mt-1.5 uppercase" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Password</label>
                            <input v-model="form.password" type="password" required class="vl-input mt-1.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Confirm password</label>
                            <input v-model="form.password_confirmation" type="password" required class="vl-input mt-1.5" />
                        </div>
                        <button type="submit" :disabled="form.processing" class="vl-btn-primary w-full">
                            Create account
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-600">
                        <Link href="/login" class="font-medium text-brand-600 hover:text-brand-700">Sign in</Link>
                        <span class="mx-2 text-slate-300">·</span>
                        <Link href="/register" class="font-medium text-slate-700 hover:text-slate-900">Incubator signup</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
