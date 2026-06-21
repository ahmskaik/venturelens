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
        label: 'Screening',
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
        label: 'Impact',
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

const trustItems = [
    { icon: 'G', label: 'Google Gemini' },
    { icon: '☁', label: 'Google Cloud Run' },
    { icon: '🏆', label: 'Gemini XPRIZE 2026' },
    { icon: '⚡', label: 'Stripe Billing' },
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
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-3.5">
                <Logo />
                <nav class="flex items-center gap-1 sm:gap-2">
                    <Link href="/impact" class="hidden rounded-full px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:inline-flex">
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
                        <Link href="/login" class="hidden rounded-full px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:inline-flex">
                            Log in
                        </Link>
                        <Link href="/register" class="vl-btn-primary ml-1 hidden sm:inline-flex">
                            For incubators
                        </Link>
                        <Link href="/founder/register" class="ml-1 rounded-full border border-slate-200 px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:ml-2">
                            For founders
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden vl-landing-surface vl-hero-glow-light">
            <div class="vl-gradient-orb -left-32 top-0 h-96 w-96 bg-brand-300/30" />
            <div class="vl-gradient-orb -right-24 top-32 h-80 w-80 bg-pink-300/20" />
            <div class="vl-gradient-orb bottom-0 left-1/2 h-64 w-64 -translate-x-1/2 bg-teal-300/15" />

            <div class="relative mx-auto max-w-6xl px-6 pb-20 pt-16 lg:pb-28 lg:pt-24">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="vl-reveal text-sm font-medium tracking-wide text-brand-600">
                        Built for incubators and accelerators
                    </p>
                    <h1 class="vl-display vl-reveal vl-reveal-delay-1 mt-5 text-4xl font-bold leading-[1.08] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        Screen startup applications in minutes, not weeks.
                    </h1>
                    <p class="vl-reveal vl-reveal-delay-2 mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-500">
                        VentureLens gives selection committees structured AI scoring, risk flags, and committee-ready summaries — with a full Gemini audit trail.
                    </p>
                    <div class="vl-reveal vl-reveal-delay-3 mt-10 flex flex-wrap justify-center gap-3">
                        <Link href="/register" class="vl-btn-primary bg-slate-900 px-7 py-3 text-base hover:bg-slate-800">
                            Start free trial
                        </Link>
                        <Link href="/impact" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-7 py-3 text-base font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                            View live impact
                        </Link>
                    </div>
                </div>

                <!-- Hero screenshot -->
                <div class="vl-reveal vl-reveal-delay-2 relative mx-auto mt-16 max-w-4xl">
                    <div class="vl-showcase-card" style="animation: vl-float 7s ease-in-out infinite">
                        <div class="vl-screenshot-frame border-0 shadow-none">
                            <div class="vl-screenshot-chrome">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400/80" />
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400/80" />
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400/80" />
                                <span class="ml-2 text-xs text-slate-400">venturelens.app — screening</span>
                            </div>
                            <div class="vl-showcase-viewport vl-showcase-viewport-hero">
                                <img
                                    src="/images/screenshots/application-screening.png"
                                    alt="VentureLens AI screening dashboard"
                                    class="vl-showcase-img"
                                    width="1200"
                                    height="750"
                                    loading="eager"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-3 left-4 hidden rounded-2xl border border-slate-200/80 bg-white/95 px-4 py-3 shadow-card-lg backdrop-blur sm:block lg:-left-6">
                        <p class="text-xs text-slate-400">Latest screening</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-900">Score 85 · Shortlist</p>
                    </div>
                    <div class="absolute -right-2 top-8 hidden rounded-2xl border border-brand-200/60 bg-brand-50/95 px-4 py-3 shadow-card backdrop-blur lg:-right-6 lg:block">
                        <p class="text-xs text-brand-600">Gemini evaluation</p>
                        <p class="mt-0.5 text-sm font-semibold text-brand-800">1.2s · 2,840 tokens</p>
                    </div>
                </div>

                <dl class="vl-reveal mx-auto mt-20 grid max-w-3xl gap-8 border-t border-slate-200/80 pt-12 sm:grid-cols-3">
                    <div class="text-center">
                        <dt class="text-3xl font-semibold tabular-nums tracking-tight text-slate-900">100%</dt>
                        <dd class="mt-1.5 text-sm text-slate-500">Applications AI-screened</dd>
                    </div>
                    <div class="text-center">
                        <dt class="text-3xl font-semibold tabular-nums tracking-tight text-slate-900">6</dt>
                        <dd class="mt-1.5 text-sm text-slate-500">Agents in production</dd>
                    </div>
                    <div class="text-center">
                        <dt class="text-3xl font-semibold tabular-nums tracking-tight text-slate-900">$199</dt>
                        <dd class="mt-1.5 text-sm text-slate-500">Per cohort package</dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Trust strip -->
        <section class="border-y border-slate-200/80 bg-white py-12">
            <div class="mx-auto max-w-6xl px-6">
                <p class="vl-reveal text-center text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                    Powered by production-grade AI infrastructure
                </p>
                <div class="vl-reveal vl-reveal-delay-1 mt-8 flex flex-wrap items-center justify-center gap-x-12 gap-y-5">
                    <span
                        v-for="item in trustItems"
                        :key="item.label"
                        class="vl-trust-logo"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold text-slate-500">
                            {{ item.icon }}
                        </span>
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Product showcase -->
        <section class="vl-landing-surface py-24 lg:py-32">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal mx-auto max-w-2xl text-center">
                    <h2 class="vl-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-[2.75rem]">
                        See VentureLens in action
                    </h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-500">
                        From intake to committee decision — one platform, fully auditable.
                    </p>
                </div>

                <div class="vl-reveal vl-reveal-delay-1 mt-10 flex flex-wrap justify-center gap-2">
                    <button
                        v-for="(shot, key) in screenshots"
                        :key="key"
                        type="button"
                        class="vl-tab-pill"
                        :class="activeScreenshot === key ? 'vl-tab-pill-active' : 'vl-tab-pill-inactive'"
                        @click="activeScreenshot = key"
                    >
                        {{ shot.label }}
                    </button>
                </div>

                <div class="vl-reveal vl-reveal-delay-2 mt-14 grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div class="order-2 lg:order-1">
                        <h3 class="text-2xl font-semibold leading-snug tracking-tight text-slate-900 sm:text-[1.65rem]">
                            {{ screenshots[activeScreenshot].title }}
                        </h3>
                        <p class="mt-4 text-base leading-relaxed text-slate-500">
                            {{ screenshots[activeScreenshot].description }}
                        </p>
                        <Link href="/register" class="vl-btn-primary mt-8 inline-flex bg-slate-900 hover:bg-slate-800">
                            Try it free
                        </Link>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="vl-showcase-card transition-opacity duration-500">
                            <div class="vl-showcase-viewport vl-showcase-viewport-tab border border-slate-200/60 shadow-inner">
                                <img
                                    :key="activeScreenshot"
                                    :src="screenshots[activeScreenshot].src"
                                    :alt="screenshots[activeScreenshot].alt"
                                    class="vl-showcase-img animate-[vl-fade-up_0.45s_ease-out]"
                                    width="1200"
                                    height="750"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two platforms -->
        <section class="relative overflow-hidden bg-slate-950 py-24 text-white lg:py-32">
            <div class="pointer-events-none absolute inset-0 vl-hero-glow opacity-50" />
            <div class="relative mx-auto max-w-6xl px-6">
                <div class="vl-reveal mx-auto max-w-2xl text-center">
                    <h2 class="vl-display text-3xl font-bold tracking-tight sm:text-4xl lg:text-[2.75rem]">
                        Two capabilities, one AI-native stack
                    </h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-400">
                        Screening for your cohorts — and agents that run the VentureLens business itself.
                    </p>
                </div>

                <div class="mt-14 grid gap-5 lg:grid-cols-2">
                    <div class="vl-reveal vl-glass-card-dark">
                        <p class="text-sm font-medium text-brand-300">Screening platform</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">Every application, scored and explained</h3>
                        <p class="mt-3 leading-relaxed text-slate-400">
                            Queue-based Gemini screening, rubric alignment, pitch deck extraction, and committee workflows with founder email drafts.
                        </p>
                        <ul class="mt-6 space-y-2.5 text-sm text-slate-300">
                            <li class="flex gap-2.5"><span class="text-brand-400">✓</span> Async screening with quota management</li>
                            <li class="flex gap-2.5"><span class="text-brand-400">✓</span> Ask RAG chat over your portfolio</li>
                            <li class="flex gap-2.5"><span class="text-brand-400">✓</span> Rescreen and audit agent executions</li>
                        </ul>
                    </div>
                    <div class="vl-reveal vl-reveal-delay-1 vl-glass-card-dark border-brand-500/20 bg-gradient-to-br from-brand-950/60 to-slate-900/80">
                        <p class="text-sm font-medium text-accent-500">AI-native operations</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">The company runs on Gemini agents</h3>
                        <p class="mt-3 leading-relaxed text-slate-400">
                            Six business agents handle growth, onboarding, support, finance, and success — with autonomy levels L0–L3 and guardrails.
                        </p>
                        <ul class="mt-6 space-y-2.5 text-sm text-slate-300">
                            <li class="flex gap-2.5"><span class="text-accent-500">✓</span> Hourly support and daily growth jobs</li>
                            <li class="flex gap-2.5"><span class="text-accent-500">✓</span> Stripe → finance agent on every charge</li>
                            <li class="flex gap-2.5"><span class="text-accent-500">✓</span> Public /impact KPIs for judges</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="bg-white py-24 lg:py-32">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal max-w-xl">
                    <h2 class="vl-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">How it works</h2>
                    <p class="mt-3 text-lg text-slate-500">
                        A straightforward pipeline from intake to committee decision.
                    </p>
                </div>

                <div class="mt-14 grid gap-5 md:grid-cols-3">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="vl-reveal vl-glass-card group transition hover:border-slate-300 hover:shadow-card-lg"
                    >
                        <span class="text-3xl font-bold tabular-nums text-slate-200 transition group-hover:text-brand-300">{{ step.num }}</span>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ step.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Agents grid -->
        <section class="border-y border-slate-200/80 vl-landing-surface py-24 lg:py-28">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-xl">
                        <h2 class="vl-display text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Six agents, production-ready</h2>
                        <p class="mt-3 text-slate-500">
                            Each agent calls Gemini, logs decisions to <code class="rounded-md bg-slate-200/60 px-1.5 py-0.5 text-sm text-slate-700">agent_executions</code>, and respects spend caps.
                        </p>
                    </div>
                    <Link
                        :href="auth?.user ? '/ai-operations' : '/register'"
                        class="vl-reveal shrink-0 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Explore AI Operations →
                    </Link>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="agent in agents"
                        :key="agent.code"
                        class="vl-reveal rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card transition hover:border-brand-200 hover:shadow-card-lg"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-900">{{ agent.name }}</span>
                            <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-700">{{ agent.code }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ agent.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Impact CTA -->
        <section class="bg-white py-24 lg:py-32">
            <div class="mx-auto max-w-6xl px-6">
                <div class="vl-reveal grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p class="text-sm font-medium text-brand-600">Category impact</p>
                        <h2 class="vl-display mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                            Evidence judges can verify
                        </h2>
                        <p class="mt-4 leading-relaxed text-slate-500">
                            Live KPIs — screened applications, Gemini API calls, jobs modeled, and revenue — on a public impact page with nightly JSON snapshots.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <Link href="/impact" class="vl-btn-primary bg-slate-900 hover:bg-slate-800">View /impact</Link>
                            <Link href="/register" class="vl-btn-secondary">Start screening</Link>
                        </div>
                    </div>
                    <div class="vl-reveal vl-reveal-delay-1">
                        <div class="vl-showcase-card">
                            <div class="vl-showcase-viewport vl-showcase-viewport-compact border border-slate-200/60">
                                <img
                                    src="/images/screenshots/impact-page.png"
                                    alt="VentureLens impact metrics page"
                                    class="vl-showcase-img"
                                    width="1200"
                                    height="750"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="relative overflow-hidden bg-slate-950 py-24 text-white lg:py-32">
            <div class="pointer-events-none absolute inset-0 vl-hero-glow opacity-60" />
            <div class="vl-gradient-orb -left-20 top-1/2 h-72 w-72 -translate-y-1/2 bg-brand-500/20" />
            <div class="vl-gradient-orb -right-20 top-1/2 h-72 w-72 -translate-y-1/2 bg-teal-500/15" />
            <div class="relative mx-auto max-w-3xl px-6 text-center">
                <h2 class="vl-reveal vl-display text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                    Ready to screen your next cohort?
                </h2>
                <p class="vl-reveal vl-reveal-delay-1 mt-4 text-lg text-slate-400">
                    Free trial includes 5 screenings. No credit card required to explore the dashboard.
                </p>
                <div class="vl-reveal vl-reveal-delay-2 mt-10 flex flex-wrap justify-center gap-3">
                    <Link href="/register" class="inline-flex items-center justify-center rounded-full bg-white px-8 py-3.5 text-base font-medium text-slate-900 transition hover:bg-slate-100">
                        Start free trial
                    </Link>
                    <Link href="/login" class="rounded-full border border-white/20 px-8 py-3.5 text-base font-medium transition hover:bg-white/10">
                        Sign in
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-800 bg-slate-950 py-14 text-slate-400">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex flex-col items-center justify-between gap-8 sm:flex-row">
                    <Logo dark href="/" />
                    <div class="flex flex-wrap justify-center gap-8 text-sm">
                        <Link href="/impact" class="transition hover:text-white">Impact</Link>
                        <Link href="/register" class="transition hover:text-white">For incubators</Link>
                        <Link href="/founder/register" class="transition hover:text-white">For founders</Link>
                        <Link href="/login" class="transition hover:text-white">Sign in</Link>
                    </div>
                </div>
                <p class="mt-10 text-center text-xs text-slate-500">
                    Powered by Google Gemini · Build with Gemini XPRIZE 2026
                </p>
            </div>
        </footer>
    </div>
</template>
