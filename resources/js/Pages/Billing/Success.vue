<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    organization: Object,
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-4xl">✓</div>
            <h1 class="mt-4 text-2xl font-bold">Payment successful</h1>
            <p class="mt-2 text-slate-600">
                Your organization is now on the <strong class="capitalize">{{ organization.plan }}</strong> plan
                with {{ organization.screenings_quota }} screenings available.
            </p>
            <div class="mt-6 flex flex-col gap-3">
                <Link href="/dashboard" class="rounded-lg bg-indigo-600 py-2 font-medium text-white hover:bg-indigo-700">Go to dashboard</Link>
                <Link href="/billing" class="text-sm text-indigo-600 hover:underline">View billing</Link>
            </div>
        </div>
    </div>
</template>
