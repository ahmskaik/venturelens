<script setup>
import { useForm, Link } from '@inertiajs/vue3';

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
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
        <div class="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold">Create your organization</h1>
            <p class="mt-1 text-sm text-slate-600">Free trial includes 5 Gemini screenings.</p>

            <form class="mt-6 space-y-4" @submit.prevent="form.post('/register')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium">Your name</label>
                        <input v-model="form.name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium">Email</label>
                        <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Password</label>
                        <input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Confirm</label>
                        <input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium">Organization name</label>
                        <input v-model="form.organization_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Country</label>
                        <input v-model="form.country_code" maxlength="2" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                    </div>
                </div>
                <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-indigo-600 py-2 font-medium text-white hover:bg-indigo-700">
                    Get started
                </button>
            </form>

            <p class="mt-4 text-center text-sm">
                <Link href="/login" class="text-indigo-600 hover:underline">Already have an account?</Link>
            </p>
        </div>
    </div>
</template>
