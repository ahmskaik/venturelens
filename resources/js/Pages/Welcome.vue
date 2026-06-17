<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Logo from '../Components/Brand/Logo.vue';
import SeoHead from '../Components/Seo/SeoHead.vue';
import { buildHomeJsonLd, seoDefaults } from '../seo/defaults.js';

defineProps({
    auth: Object,
});

const page = usePage();
const homeJsonLd = computed(() => buildHomeJsonLd(page.props.seo?.appUrl ?? '', page.props.seo ?? {}));

const activeScreenshot = ref('screening');
const landingRoot = ref(null);
let revealObserver = null;

const screenshots = {
    screening: {
        label: 'AI screening',
        title: 'Structured scores and risk flags on every application',
        description: 'Gemini evaluates pitch decks against your rubric. Scores, strengths, weaknesses, and recommendations — logged with model, latency, and tokens.',
        src: '/images/screenshots/application-screening.png',
        alt: 'VentureLens application screening detail with AI score',
    },
    agents: {
        label: 'AI operations',
        title: 'Six agents operating the business',
        description: 'Growth, onboarding, support, screening, finance, and success agents run on schedules and triggers — every action logged with autonomy level.',
        src: '/images/screenshots/ai-operations-dashboard.png',
        alt: 'VentureLens AI Operations dashboard',
    },
    impact: {
        label: 'Public impact',
        title: 'Judge-ready evidence, live from production',
        description: 'Competition metrics, screened applications, and revenue KPIs on a public /impact page — snapshotted nightly for the evidence pack.',
        src: '/images/screenshots/impact-page.png',
        alt: 'VentureLens public impact page',
    },
    billing: {
        label: 'Billing',
        title: 'Stripe checkout and plan management',
        description: 'Cohort packages and subscriptions with arms-length revenue classification for competition reporting.',
        src: '/images/screenshots/billing-split.png',
        alt: 'VentureLens billing page',
    },
};

const agents = [
    { name: 'Screening', code: 'A4', desc: 'Gemini scores every submitted application' },
    { name: 'Growth', code: 'A1', desc: 'Daily outreach and pipeline signals' },
    { name: 'Onboarding', code: 'A2', desc: 'Programs and rubrics on signup' },
    { name: 'Support', code: 'A3', desc: 'RAG chat and ticket triage' },
    { name: 'Finance', code: 'A5', desc: 'Stripe charge classification' },
    { name: 'Success', code: 'A6', desc: 'Testimonial requests on payment' },
];

const steps = [
    {
        num: '01',
        title: 'Intake',
        text: 'Founders apply via cohort links with pitch decks. Submissions queue automatically.',
    },
    {
        num: '02',
        title: 'AI screening',
        text: 'Gemini extracts documents, scores against your rubric, and surfaces risk flags.',
    },
    {
        num: '03',
        title: 'Committee decision',
        text: 'Accept, shortlist, or reject with AI-prepared context and founder comms drafts.',
    },
];

onMounted(() => {
    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    landingRoot.value?.querySelectorAll('.vl-reveal').forEach((el) => {
        revealObserver.observe(el);
    });
});

onUnmounted(() => {
    revealObserver?.disconnect();
});
</script>

<template>
    <SeoHead
        :title="seoDefaults.home.title"
        :description="seoDefaults.home.description"
        :keywords="seoDefaults.home.keywords"
        url="/"
        :json-ld="homeJsonLd"
    />

    <div ref="landingRoot" class="min-h-screen bg-white text-slate-900">
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Logo dark />
                <nav class="flex items-center gap-1 sm:gap-2">
                    <Link href="/impact" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
                        Impact
                    </Link>
                    <Link
                        v-if="auth?.user"
                        :href="auth.user.account_type === 'founder' ? '/founder/dashboard' : '/dashboard'"
                        class="vl-btn-primary ml-1"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white sm:inline-flex">
                            Log in
                        </Link>
                        <Link href="/register" class="vl-btn-primary ml-1 hidden sm:inline-flex">
                            For incubators
                        </Link>
                        <Link href="/founder/register" class="ml-1 rounded-lg border border-white/20 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:ml-2">
                            For founders
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero (dark) -->
        <section class="relative overflow-hidden bg-slate-950 text-white vl-hero-glow">
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#ffffff08_1px,transparent_1px),linear-gradient(to_bottom,#ffffff08_1px,transparent_1px)] bg-[size:48px_48px]" />

            <div class="relative mx-auto max-w-6xl px-6 pb-16 pt-16 lg:pb-24 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p class="vl-reveal text-sm font-medium text-brand-300">
                            Built for incubators and accelerators
                        </p>
                        <h1 class="vl-display vl-reveal vl-reveal-delay-1 mt-4 text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-[3.25rem]">
                            Screen startup applications in minutes, not weeks.
                        </h1>
                        <p class="vl-reveal vl-reveal-delay-2 mt-6 text-lg leading-relaxed text-slate-400">
                            VentureLens gives selection committees structured AI scoring, risk flags, and committee-ready summaries — with a full Gemini audit trail.
                        </p>
                        <div class="vl-reveal vl-reveal-delay-3 mt-10 flex flex-wrap gap-3">
                            <Link href="/register" class="vl-btn-primary px-6 py-2.5 text-base">
                                Start free trial
                            </Link>
                            <Link href="/impact" class="inline-flex items-center justify-center rounded-lg border border-white/20 px-6 py-2.5 text-base font-medium text-white transition hover:bg-white/10">
                                View live impact
                            </Link>
                        </div>
                        <dl class="vl-reveal mt-14 grid gap-6 border-t border-white/10 pt-10 sm:grid-cols-3">
                            <div>
                                <dt class="text-2xl font-semibold tabular-nums text-white">100%</dt>
                                <dd class="mt-1 text-sm text-slate-400">Applications AI-screened</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-semibold tabular-nums text-white">6</dt>
                                <dd class="mt-1 text-sm text-slate-400">Agents in production</dd>
                            </div>
                            <div>
                                <dt class="text-2xl font-semibold tabular-nums text-white">$199</dt>
                                <dd class="mt-1 text-sm text-slate-400">Per cohort package</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Hero screencast -->
                    <div class="vl-reveal vl-reveal-delay-2 relative">
                        <div
                            class="vl-screenshot-frame"
                            style="animation: vl-float 6s ease-in-out infinite"
                        >
                            <div class="vl-screenshot-chrome">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400/80" />
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80" />
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80" />
                                <span class="ml-2 text-xs text-slate-400">venturelens.app — screening</span>
                            </div>
                            <img
                                src="/images/screenshots/application-screening.png"
                                alt="VentureLens AI screening dashboard"
                                class="block w-full"
                                width="1200"
                                height="750"
                                loading="eager"
                            />
                        </div>
                        <div class="absolute -bottom-4 -left-4 hidden rounded-xl border border-white/10 bg-slate-900/95 px-4 py-3 shadow-xl backdrop-blur sm:block">
                            <p class="text-xs text-slate-400">Latest screening</p>
                            <p class="mt-0.5 text-sm font-semibold text-white">Score 84 · Shortlist</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust strip (light) -->
        <section class="border-b border-slate-200 bg-white py-10">
            <div class="mx-auto max-w-6xl px-6">
                <p class="vl-reveal text-center text-xs font-medium uppercase tracking-widest text-slate-400">
                    Powered by production-grade AI infrastructure
                </p>
                <div class="vl-reveal vl-reveal-delay-1 mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4 text-sm font-semibold text-slate-500">
                    <span class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs">G</span>
                        Google Gemini
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs">☁</span>
                        Google Cloud Run
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs">🏆</span>
                        Gemini XPRIZE 2026
                    </span>
                    <span class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-xs">⚡</span>
                        Stripe Billing
                    </span>
                </div>
            </div>
        </section>

        <!-- Product showcase (light) -->
        <section class="bg-slate-50 py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal mx-auto max-w-2xl text-center">
                    <h2 class="vl-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        See VentureLens in action
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        From intake to committee decision — one platform, fully auditable.
                    </p>
                </div>

                <div class="vl-reveal vl-reveal-delay-1 mt-10 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="(shot, key) in screenshots"
                        :key="key"
                        type="button"
                        class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
                        :class="activeScreenshot === key
                            ? 'border-brand-600 bg-brand-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        @click="activeScreenshot = key"
                    >
                        {{ shot.label }}
                    </button>
                </div>

                <div class="vl-reveal vl-reveal-delay-2 mt-12 grid items-center gap-10 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <h3 class="text-xl font-semibold text-slate-900">
                            {{ screenshots[activeScreenshot].title }}
                        </h3>
                        <p class="mt-3 leading-relaxed text-slate-600">
                            {{ screenshots[activeScreenshot].description }}
                        </p>
                        <Link href="/register" class="vl-btn-primary mt-6 inline-flex">
                            Try it free
                        </Link>
                    </div>
                    <div class="lg:col-span-3">
                        <div class="vl-screenshot-frame border-slate-300 bg-white shadow-card-lg transition-opacity duration-500">
                            <div class="flex items-center gap-1.5 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400" />
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400" />
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400" />
                                <span class="ml-2 text-xs text-slate-500">venturelens.app</span>
                            </div>
                            <img
                                :key="activeScreenshot"
                                :src="screenshots[activeScreenshot].src"
                                :alt="screenshots[activeScreenshot].alt"
                                class="block w-full animate-[vl-fade-up_0.5s_ease-out]"
                                width="1200"
                                height="750"
                                loading="lazy"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two platforms (dark) — ElevenLabs-style split -->
        <section class="bg-slate-950 py-20 text-white lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal mx-auto max-w-2xl text-center">
                    <h2 class="vl-display text-3xl font-bold tracking-tight sm:text-4xl">
                        Two capabilities, one AI-native stack
                    </h2>
                    <p class="mt-4 text-lg text-slate-400">
                        Screening for your cohorts — and agents that run the VentureLens business itself.
                    </p>
                </div>

                <div class="mt-14 grid gap-6 lg:grid-cols-2">
                    <div class="vl-reveal overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur-sm">
                        <p class="text-sm font-medium text-brand-300">Screening platform</p>
                        <h3 class="mt-2 text-2xl font-semibold">Every application, scored and explained</h3>
                        <p class="mt-3 text-slate-400 leading-relaxed">
                            Queue-based Gemini screening, rubric alignment, pitch deck extraction, and committee workflows with founder email drafts.
                        </p>
                        <ul class="mt-6 space-y-2 text-sm text-slate-300">
                            <li class="flex gap-2"><span class="text-brand-400">✓</span> Async screening with quota management</li>
                            <li class="flex gap-2"><span class="text-brand-400">✓</span> Ask RAG chat over your portfolio</li>
                            <li class="flex gap-2"><span class="text-brand-400">✓</span> Rescreen and audit agent executions</li>
                        </ul>
                    </div>
                    <div class="vl-reveal vl-reveal-delay-1 overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-brand-950/80 to-slate-900 p-8">
                        <p class="text-sm font-medium text-accent-500">AI-native operations</p>
                        <h3 class="mt-2 text-2xl font-semibold">The company runs on Gemini agents</h3>
                        <p class="mt-3 text-slate-400 leading-relaxed">
                            Six business agents handle growth, onboarding, support, finance, and success — with autonomy levels L0–L3 and guardrails.
                        </p>
                        <ul class="mt-6 space-y-2 text-sm text-slate-300">
                            <li class="flex gap-2"><span class="text-accent-500">✓</span> Hourly support and daily growth jobs</li>
                            <li class="flex gap-2"><span class="text-accent-500">✓</span> Stripe → finance agent on every charge</li>
                            <li class="flex gap-2"><span class="text-accent-500">✓</span> Public /impact KPIs for judges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works (light) -->
        <section class="bg-white py-20 lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal max-w-xl">
                    <h2 class="vl-display text-3xl font-bold tracking-tight text-slate-900">How it works</h2>
                    <p class="mt-3 text-lg text-slate-600">
                        A straightforward pipeline from intake to committee decision.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="vl-reveal vl-card group p-8 transition hover:border-brand-200 hover:shadow-card-lg"
                    >
                        <span class="text-3xl font-bold tabular-nums text-brand-200 transition group-hover:text-brand-400">{{ step.num }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ step.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Agents grid (dark) -->
        <section class="border-y border-white/10 bg-slate-900 py-20 text-white lg:py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-xl">
                        <h2 class="vl-display text-3xl font-bold tracking-tight">Six agents, production-ready</h2>
                        <p class="mt-3 text-slate-400">
                            Each agent calls Gemini, logs decisions to <code class="rounded bg-white/10 px-1.5 py-0.5 text-sm text-brand-200">agent_executions</code>, and respects spend caps.
                        </p>
                    </div>
                    <Link
                        :href="auth?.user ? '/ai-operations' : '/register'"
                        class="vl-reveal shrink-0 rounded-lg border border-white/20 px-5 py-2.5 text-sm font-medium transition hover:bg-white/10"
                    >
                        Explore AI Operations →
                    </Link>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="agent in agents"
                        :key="agent.code"
                        class="vl-reveal rounded-xl border border-white/10 bg-white/5 p-5 transition hover:border-brand-500/40 hover:bg-white/10"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">{{ agent.name }}</span>
                            <span class="rounded-md bg-brand-500/20 px-2 py-0.5 text-xs font-medium text-brand-200">{{ agent.code }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">{{ agent.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Impact CTA (light) -->
        <section class="bg-brand-50 py-20 lg:py-24">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-brand-700">Category impact</p>
                        <h2 class="vl-display mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            Evidence judges can verify
                        </h2>
                        <p class="mt-4 text-slate-600 leading-relaxed">
                            Live KPIs — screened applications, Gemini API calls, jobs modeled, and revenue — on a public impact page with nightly JSON snapshots.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link href="/impact" class="vl-btn-primary">View /impact</Link>
                            <Link href="/register" class="vl-btn-secondary">Start screening</Link>
                        </div>
                    </div>
                    <div class="vl-reveal vl-reveal-delay-1">
                        <div class="overflow-hidden rounded-2xl border border-brand-200 bg-white shadow-card-lg">
                            <img
                                src="/images/screenshots/impact-page.png"
                                alt="VentureLens impact metrics page"
                                class="block w-full"
                                width="1200"
                                height="750"
                                loading="lazy"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA (dark) -->
        <section class="relative overflow-hidden bg-slate-950 py-20 text-white lg:py-28">
            <div class="pointer-events-none absolute inset-0 vl-hero-glow opacity-60" />
            <div class="relative mx-auto max-w-3xl px-6 text-center">
                <h2 class="vl-reveal vl-display text-3xl font-bold tracking-tight sm:text-4xl">
                    Ready to screen your next cohort?
                </h2>
                <p class="vl-reveal vl-reveal-delay-1 mt-4 text-lg text-slate-400">
                    Free trial includes 5 screenings. No credit card required to explore the dashboard.
                </p>
                <div class="vl-reveal vl-reveal-delay-2 mt-10 flex flex-wrap justify-center gap-3">
                    <Link href="/register" class="vl-btn-primary px-8 py-3 text-base">
                        Start free trial
                    </Link>
                    <Link href="/login" class="rounded-lg border border-white/20 px-8 py-3 text-base font-medium transition hover:bg-white/10">
                        Sign in
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-800 bg-slate-950 py-12 text-slate-400">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
                    <Logo dark href="/" />
                    <div class="flex flex-wrap justify-center gap-6 text-sm">
                        <Link href="/impact" class="transition hover:text-white">Impact</Link>
                        <Link href="/register" class="transition hover:text-white">For incubators</Link>
                        <Link href="/founder/register" class="transition hover:text-white">For founders</Link>
                        <Link href="/login" class="transition hover:text-white">Sign in</Link>
                    </div>
                </div>
                <p class="mt-8 text-center text-xs text-slate-500">
                    Powered by Google Gemini · Build with Gemini XPRIZE 2026
                </p>
            </div>
        </footer>
    </div>
</template>
