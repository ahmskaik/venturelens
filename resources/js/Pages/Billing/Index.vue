<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AppShell from '../../Components/Layout/AppShell.vue';
import StatCard from '../../Components/Ui/StatCard.vue';

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
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/billing/checkout/${plan}`;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = token;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}
</script>

<template>
    <AppShell
        title="Billing"
        subtitle="Subscription plan, usage, and payment history."
    >
        <div v-if="page.props.flash?.error" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ page.props.flash.error }}
        </div>

        <div v-if="!stripe.ready" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Stripe checkout not ready</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                <li v-if="!stripe.secret_configured">Set <code>STRIPE_SECRET</code> in .env</li>
                <li v-if="!stripe.cohort_configured">Run <code>php artisan stripe:ensure-prices</code> and set <code>STRIPE_PRICE_COHORT</code></li>
                <li v-if="!stripe.starter_configured">Run <code>php artisan stripe:ensure-prices</code> and set <code>STRIPE_PRICE_STARTER</code></li>
            </ul>
        </div>

        <section class="vl-card-elevated p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Current plan</p>
                    <p class="vl-display mt-1 text-4xl font-bold capitalize text-slate-900">{{ organization.plan }}</p>
                    <p class="mt-2 text-slate-600">
                        Screenings: {{ organization.screenings_used }} / {{ organization.screenings_quota }} used
                    </p>
                </div>
                <Link v-if="has_stripe_customer" href="/billing/portal" class="vl-btn-secondary text-sm">
                    Manage in Stripe
                </Link>
            </div>
            <div
                v-if="organization.screenings_used >= organization.screenings_quota * 0.8"
                class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                You're approaching your screening limit. Upgrade to keep screening applications.
            </div>
        </section>

        <section class="mt-8 grid gap-4 sm:grid-cols-2">
            <div
                v-for="plan in plans.filter(p => p.key !== 'free')"
                :key="plan.key"
                class="vl-card p-6"
                :class="{ 'ring-2 ring-brand-500': organization.plan === plan.key }"
            >
                <h3 class="vl-display text-lg font-bold">{{ plan.name }}</h3>
                <p class="mt-2 text-3xl font-bold text-brand-700">{{ plan.price_label }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ plan.quota }} screenings{{ plan.key === 'starter' ? '/month' : '' }}</p>
                <button
                    v-if="organization.plan !== plan.key && stripe.ready"
                    class="vl-btn-primary mt-6 w-full"
                    @click="checkout(plan.key)"
                >
                    Upgrade to {{ plan.name }}
                </button>
                <button
                    v-else-if="organization.plan !== plan.key"
                    disabled
                    class="mt-6 w-full cursor-not-allowed rounded-xl bg-slate-200 py-2.5 text-sm font-medium text-slate-500"
                >
                    Configure Stripe to upgrade
                </button>
                <p v-else class="mt-6 text-sm font-semibold text-emerald-600">✓ Current plan</p>
            </div>
        </section>

        <section class="mt-8">
            <h2 class="vl-display text-lg font-bold">Revenue tracking (Devpost)</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <StatCard label="Arms-length" :value="`$${revenue.arms_length_usd}`" variant="success" />
                <StatCard label="Related-party" :value="`$${revenue.related_party_usd}`" />
                <StatCard label="Total" :value="`$${revenue.total_usd}`" variant="brand" />
            </div>

            <div v-if="charges.length" class="vl-card mt-6 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Plan</th>
                            <th class="px-5 py-3">Amount</th>
                            <th class="px-5 py-3">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(c, i) in charges" :key="i" class="border-t border-slate-100">
                            <td class="px-5 py-3">{{ c.paid_at?.slice(0, 10) }}</td>
                            <td class="px-5 py-3 capitalize">{{ c.plan }}</td>
                            <td class="px-5 py-3 font-semibold">${{ c.amount_usd }}</td>
                            <td class="px-5 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="c.revenue_type === 'arms_length' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                >
                                    {{ c.revenue_type.replace('_', ' ') }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppShell>
</template>
