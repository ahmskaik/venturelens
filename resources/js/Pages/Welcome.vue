<script setup>
import { Link } from '@inertiajs/vue3';
import Logo from '../Components/Brand/Logo.vue';
import GeminiBadge from '../Components/Brand/GeminiBadge.vue';

defineProps({
    auth: Object,
});
</script>

<template>
    <div class="min-h-screen bg-white">
        <!-- Nav -->
        <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Logo />
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/impact" class="vl-btn-ghost hidden sm:inline-flex">Impact</Link>
                    <Link v-if="auth?.user" :href="auth.user.account_type === 'founder' ? '/founder/dashboard' : '/dashboard'" class="vl-btn-primary">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="vl-btn-ghost">Log in</Link>
                        <Link href="/register" class="vl-btn-primary hidden sm:inline-flex">Incubator</Link>
                        <Link href="/founder/register" class="vl-btn-secondary border-emerald-200 text-emerald-700">Founders</Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="vl-gradient-bg relative overflow-hidden pt-28 pb-20 text-white">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-violet-600/20 via-transparent to-transparent" />
            <div class="absolute -right-32 top-20 h-96 w-96 rounded-full bg-violet-500/20 blur-3xl" />
            <div class="absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-indigo-500/15 blur-3xl" />

            <div class="relative mx-auto max-w-6xl px-6">
                <div class="flex flex-wrap items-center gap-3">
                    <GeminiBadge size="lg" />
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium text-violet-200">
                        Build with Gemini XPRIZE
                    </span>
                </div>

                <h1 class="vl-display mt-8 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Screen every startup with
                    <span class="bg-gradient-to-r from-violet-300 to-emerald-300 bg-clip-text text-transparent"> Gemini AI </span>
                    — in minutes, not weeks.
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-300">
                    VentureLens is an AI-operated platform for incubators and accelerators. Every application is screened by Gemini.
                    Six autonomous agents run our business — screening, growth, support, and finance — in production.
                </p>

                <div class="mt-10 flex flex-wrap gap-4">
                    <Link href="/register" class="vl-btn-primary bg-white text-brand-700 hover:bg-violet-50">
                        Start free — 5 screenings
                    </Link>
                    <Link href="/login" class="vl-btn-secondary border-white/20 bg-white/10 text-white hover:bg-white/20">
                        Judge demo login →
                    </Link>
                </div>

                <!-- Stats strip for video -->
                <div class="mt-16 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                        <p class="text-3xl font-bold text-emerald-400">100%</p>
                        <p class="mt-1 text-sm text-slate-400">Applications screened by Gemini</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                        <p class="text-3xl font-bold text-violet-300">6</p>
                        <p class="mt-1 text-sm text-slate-400">AI agents run the business</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                        <p class="text-3xl font-bold text-white">$199</p>
                        <p class="mt-1 text-sm text-slate-400">Per cohort — real SaaS revenue</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <p class="text-center text-sm font-semibold uppercase tracking-wider text-brand-600">How it works</p>
            <h2 class="vl-display mt-2 text-center text-3xl font-bold text-slate-900">From application to decision</h2>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                <div class="vl-card-elevated p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                        <span class="text-xl font-bold">1</span>
                    </div>
                    <h3 class="vl-display mt-5 text-lg font-bold">Intake</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Founders apply with forms and pitch decks. Every submission enters the AI pipeline automatically.</p>
                </div>
                <div class="vl-card-elevated p-8 ring-2 ring-brand-200">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                        <span class="text-xl font-bold">2</span>
                    </div>
                    <h3 class="vl-display mt-5 text-lg font-bold">Gemini screening</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Structured scores, risk flags, and committee-ready summaries — with full agent execution logs.</p>
                    <GeminiBadge class="mt-4" />
                </div>
                <div class="vl-card-elevated p-8">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <span class="text-xl font-bold">3</span>
                    </div>
                    <h3 class="vl-display mt-5 text-lg font-bold">Decision & impact</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Committee decides with AI assistance. Founder feedback drafted by Gemini. Impact tracked live.</p>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-slate-50 py-10 text-center text-sm text-slate-500">
            <div class="flex justify-center">
                <Logo href="/" />
            </div>
            <p class="mt-4">Entrepreneurship & Job Creation · Build with Gemini XPRIZE 2026</p>
        </footer>
    </div>
</template>
