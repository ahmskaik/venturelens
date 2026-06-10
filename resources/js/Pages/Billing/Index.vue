<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    organization: Object,
    plans: Array,
    revenue: Object,
    charges: Array,
    has_stripe_customer: Boolean,
    subscription_active: Boolean,
    stripe: Object,
});

const page = usePage();

function checkout(plan) {
    router.post(`/billing/checkout/${plan}`);
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <header class="border-b border-slate-200 bg-white px-6 py-4">
            <Link href="/dashboard" class="text-sm text-indigo-600 hover:underline">← Dashboard</Link>
            <h1 class="mt-2 text-2xl font-bold">Billing</h1>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8 space-y-8">
            <div v-if="page.props.flash?.error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ page.props.flash.error }}
            </div>

            <div v-if="!stripe.ready" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-medium">Stripe checkout not ready</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li v-if="!stripe.secret_configured">Set <code>STRIPE_SECRET</code> in .env</li>
                    <li v-if="!stripe.cohort_configured">Run <code>php artisan stripe:ensure-prices</code> and set <code>STRIPE_PRICE_COHORT</code></li>
                    <li v-if="!stripe.starter_configured">Run <code>php artisan stripe:ensure-prices</code> and set <code>STRIPE_PRICE_STARTER</code></li>
                </ul>
            </div>
            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Current plan</h2>
                <p class="mt-2 text-3xl font-bold capitalize">{{ organization.plan }}</p>
                <p class="mt-1 text-sm text-slate-600">
                    Screenings: {{ organization.screenings_used }} / {{ organization.screenings_quota }} used
                </p>
                <div v-if="organization.screenings_used >= organization.screenings_quota * 0.8" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    You're approaching your screening limit. Upgrade to keep screening applications.
                </div>
                <Link v-if="has_stripe_customer" href="/billing/portal" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">
                    Manage subscription in Stripe →
                </Link>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div v-for="plan in plans.filter(p => p.key !== 'free')" :key="plan.key" class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="font-semibold">{{ plan.name }}</h3>
                    <p class="mt-1 text-2xl font-bold">{{ plan.price_label }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ plan.quota }} screenings{{ plan.key === 'starter' ? '/month' : '' }}</p>
                    <button
                        v-if="organization.plan !== plan.key && stripe.ready"
                        class="mt-4 w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="checkout(plan.key)"
                    >
                        Upgrade to {{ plan.name }}
                    </button>
                    <button
                        v-else-if="organization.plan !== plan.key"
                        disabled
                        class="mt-4 w-full cursor-not-allowed rounded-lg bg-slate-300 py-2 text-sm font-medium text-slate-600"
                    >
                        Configure Stripe to upgrade
                    </button>
                    <p v-else class="mt-4 text-sm text-green-600 font-medium">Current plan</p>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold">Revenue tracking (Devpost)</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-sm text-slate-500">Arms-length</p>
                        <p class="text-xl font-bold text-green-700">${{ revenue.arms_length_usd }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Related-party</p>
                        <p class="text-xl font-bold text-amber-700">${{ revenue.related_party_usd }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Total</p>
                        <p class="text-xl font-bold">${{ revenue.total_usd }}</p>
                    </div>
                </div>
                <table v-if="charges.length" class="mt-6 w-full text-sm">
                    <thead class="text-left text-slate-500">
                        <tr>
                            <th class="pb-2">Date</th>
                            <th class="pb-2">Plan</th>
                            <th class="pb-2">Amount</th>
                            <th class="pb-2">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in charges" :key="i" class="border-t border-slate-100">
                            <td class="py-2">{{ c.paid_at?.slice(0, 10) }}</td>
                            <td class="py-2 capitalize">{{ c.plan }}</td>
                            <td class="py-2">${{ c.amount_usd }}</td>
                            <td class="py-2">
                                <span :class="c.revenue_type === 'arms_length' ? 'text-green-700' : 'text-amber-700'">
                                    {{ c.revenue_type.replace('_', ' ') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</template>
