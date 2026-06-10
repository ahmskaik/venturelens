<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\BillingService;
use App\Services\RevenueClassifier;
use App\Services\StripePriceResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Cashier;

class BillingController extends Controller
{
    public function index(Request $request, BillingService $billing, StripePriceResolver $prices): Response
    {
        $organization = $this->organization($request);
        $revenue = $billing->revenueSummary($organization);
        $stripe = $prices->status();

        return Inertia::render('Billing/Index', [
            'organization' => [
                'name' => $organization->name,
                'plan' => $organization->plan,
                'screenings_used' => $organization->screenings_used,
                'screenings_quota' => $organization->screenings_quota,
            ],
            'plans' => collect(config('venturelens.plans'))
                ->only(['free', 'cohort', 'starter'])
                ->map(fn ($p, $key) => array_merge($p, ['key' => $key]))
                ->values(),
            'revenue' => [
                'arms_length_usd' => round($revenue['arms_length_cents'] / 100, 2),
                'related_party_usd' => round($revenue['related_party_cents'] / 100, 2),
                'total_usd' => round($revenue['total_cents'] / 100, 2),
            ],
            'charges' => $organization->revenueCharges()
                ->latest('paid_at')
                ->limit(10)
                ->get()
                ->map(fn ($c) => [
                    'plan' => $c->plan,
                    'amount_usd' => $c->amountUsd(),
                    'revenue_type' => $c->revenue_type,
                    'paid_at' => $c->paid_at?->toIso8601String(),
                ]),
            'has_stripe_customer' => $organization->hasStripeId(),
            'subscription_active' => $organization->subscribed('default'),
            'stripe' => [
                'ready' => $stripe['ready'],
                'secret_configured' => $stripe['secret_configured'],
                'cohort_configured' => (bool) $stripe['cohort'],
                'starter_configured' => (bool) $stripe['starter'],
            ],
        ]);
    }

    public function checkout(Request $request, string $plan, RevenueClassifier $classifier, StripePriceResolver $priceResolver): RedirectResponse|\Laravel\Cashier\Checkout
    {
        abort_unless(in_array($plan, ['cohort', 'starter'], true), 404);

        $organization = $this->organization($request);
        $user = $request->user();
        $priceId = $priceResolver->resolve($plan);

        if (empty($priceId)) {
            return redirect()->route('billing.index')->with(
                'error',
                'Stripe is not fully configured. Run `php artisan stripe:ensure-prices` and add STRIPE_PRICE_'.strtoupper($plan).' to .env'
            );
        }

        $revenueType = $classifier->classify($organization, $user);
        $metadata = [
            'organization_id' => (string) $organization->id,
            'plan' => $plan,
            'revenue_type' => $revenueType,
        ];

        $sessionOptions = [
            'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.index'),
            'metadata' => $metadata,
            'client_reference_id' => (string) $organization->id,
        ];

        if ($plan === 'cohort') {
            return $organization->checkout(
                [['price' => $priceId, 'quantity' => 1]],
                array_merge($sessionOptions, ['mode' => 'payment']),
            );
        }

        return $organization->newSubscription('default', $priceId)
            ->checkout($sessionOptions);
    }

    public function success(Request $request, BillingService $billing): Response
    {
        $organization = $this->organization($request);
        $sessionId = $request->query('session_id');

        if ($sessionId && $organization->hasStripeId()) {
            $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);
            $billing->fulfillCheckoutSession($organization, $session->toArray(), $request->user());
            $organization->refresh();
        }

        return Inertia::render('Billing/Success', [
            'organization' => [
                'plan' => $organization->plan,
                'screenings_quota' => $organization->screenings_quota,
            ],
        ]);
    }

    public function portal(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);

        return $organization->redirectToBillingPortal(route('billing.index'));
    }

    private function organization(Request $request): Organization
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404, 'No organization found.');

        return $organization;
    }
}
