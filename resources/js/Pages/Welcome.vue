<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Logo from '../Components/Brand/Logo.vue';
import SeoHead from '../Components/Seo/SeoHead.vue';
import { buildHomeJsonLd, seoDefaults } from '../seo/defaults.js';

defineProps({
    auth: Object,
});

const page = usePage();
const homeJsonLd = computed(() => buildHomeJsonLd(page.props.seo?.appUrl ?? '', page.props.seo ?? {}));
</script>

<template>
    <SeoHead
        :title="seoDefaults.home.title"
        :description="seoDefaults.home.description"
        :keywords="seoDefaults.home.keywords"
        url="/"
        :json-ld="homeJsonLd"
    />
    <div class="min-h-screen bg-white">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Logo />
                <nav class="flex items-center gap-2 text-sm">
                    <Link href="/impact" class="vl-btn-ghost hidden sm:inline-flex">Impact</Link>
                    <Link v-if="auth?.user" :href="auth.user.account_type === 'founder' ? '/founder/dashboard' : '/dashboard'" class="vl-btn-primary">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="vl-btn-ghost">Log in</Link>
                        <Link href="/register" class="vl-btn-primary hidden sm:inline-flex">For incubators</Link>
                        <Link href="/founder/register" class="vl-btn-secondary">For founders</Link>
                    </template>
                </nav>
            </div>
        </header>

        <section class="border-b border-slate-200 bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
                <p class="text-sm font-medium text-brand-600">Built for incubators and accelerators</p>

                <h1 class="vl-display mt-4 max-w-3xl text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    Screen startup applications in minutes, not weeks.
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
                    VentureLens helps selection committees review every application with structured AI scoring,
                    risk flags, and committee-ready summaries — backed by a full audit trail.
                </p>

                <div class="mt-10 flex flex-wrap gap-3">
                    <Link href="/register" class="vl-btn-primary px-6 py-2.5">
                        Start free trial
                    </Link>
                    <Link href="/login" class="vl-btn-secondary px-6 py-2.5">
                        Sign in
                    </Link>
                </div>

                <dl class="mt-16 grid gap-6 border-t border-slate-200 pt-10 sm:grid-cols-3">
                    <div>
                        <dt class="text-2xl font-semibold tabular-nums text-slate-900">100%</dt>
                        <dd class="mt-1 text-sm text-slate-600">Applications receive AI screening</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-semibold tabular-nums text-slate-900">6</dt>
                        <dd class="mt-1 text-sm text-slate-600">Operational agents in production</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-semibold tabular-nums text-slate-900">$199</dt>
                        <dd class="mt-1 text-sm text-slate-600">Per cohort, subscription pricing</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-6 py-20">
            <h2 class="text-2xl font-semibold text-slate-900">How it works</h2>
            <p class="mt-2 max-w-2xl text-slate-600">A straightforward pipeline from intake to committee decision.</p>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="vl-card p-6">
                    <p class="text-sm font-medium text-slate-500">Step 1</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">Intake</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Founders submit applications with pitch decks. Each submission enters the screening queue automatically.
                    </p>
                </div>
                <div class="vl-card border-brand-200 p-6">
                    <p class="text-sm font-medium text-brand-600">Step 2</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">AI screening</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Structured scores, risk flags, and summaries — with model, latency, and token usage logged for audit.
                    </p>
                </div>
                <div class="vl-card p-6">
                    <p class="text-sm font-medium text-slate-500">Step 3</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">Decision</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Committees decide with AI-prepared context. Founder communications drafted for review before sending.
                    </p>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-slate-50 py-10">
            <div class="mx-auto max-w-6xl px-6 text-center text-sm text-slate-500">
                <div class="flex justify-center">
                    <Logo href="/" />
                </div>
                <p class="mt-4">Powered by Google Gemini · Build with Gemini XPRIZE 2026</p>
            </div>
        </footer>
    </div>
</template>
