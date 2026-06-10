<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Log in</h1>
            <p class="mt-1 text-sm text-slate-600">
                Demo: {{ $page.props.auth ? '' : '' }}demo@venturelens.app
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="form.post('/login')">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email</label>
                    <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Password</label>
                    <input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </div>
                <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-indigo-600 py-2 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    Log in
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-slate-600">
                <Link href="/register" class="text-indigo-600 hover:underline">Create an account</Link>
            </p>
        </div>
    </div>
</template>
