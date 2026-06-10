<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    auth: Object,
});
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-xl font-bold text-indigo-600">VentureLens</Link>
                <nav class="flex gap-4 text-sm">
                    <Link href="/impact" class="text-slate-600 hover:text-indigo-600">Impact</Link>
                    <Link v-if="auth?.user" href="/dashboard" class="text-slate-600 hover:text-indigo-600">Dashboard</Link>
                    <template v-else>
                        <Link href="/login" class="text-slate-600 hover:text-indigo-600">Log in</Link>
                        <Link href="/register" class="rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Start free</Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-16">
            <div class="max-w-2xl">
                <p class="text-sm font-medium text-indigo-600">Build with Gemini XPRIZE</p>
                <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-900">
                    Screen startup applications with Gemini — in minutes, not weeks.
                </h1>
                <p class="mt-4 text-lg text-slate-600">
                    VentureLens helps incubators and accelerators evaluate founders faster with AI-powered screening,
                    committee reports, and automated feedback. Every application is screened by Gemini before human review.
                </p>
                <div class="mt-8 flex gap-4">
                    <Link href="/register" class="rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white hover:bg-indigo-700">
                        Start free — 5 screenings included
                    </Link>
                    <Link href="/apply/summer-2026" class="rounded-lg border border-slate-300 px-6 py-3 font-medium text-slate-700 hover:bg-slate-50">
                        Try demo apply form
                    </Link>
                </div>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold">Gemini screening</h3>
                    <p class="mt-2 text-sm text-slate-600">Structured scores, risk flags, and committee-ready summaries for every application.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold">Configurable rubrics</h3>
                    <p class="mt-2 text-sm text-slate-600">Weighted criteria passed to Gemini as structured JSON for consistent evaluation.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold">Production on Cloud Run</h3>
                    <p class="mt-2 text-sm text-slate-600">Async queue workers, GCS file storage, and full agent execution audit trail.</p>
                </div>
            </div>
        </main>
    </div>
</template>
