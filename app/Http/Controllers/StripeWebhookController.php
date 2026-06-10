<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\BillingService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];
        $organization = $this->findOrganization($session);

        if ($organization && ($session['mode'] ?? '') === 'payment') {
            app(BillingService::class)->fulfillCheckoutSession($organization, $session);
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $invoice = $payload['data']['object'];
        $organization = Organization::where('stripe_id', $invoice['customer'] ?? '')->first();

        if ($organization && ($invoice['amount_paid'] ?? 0) > 0) {
            app(BillingService::class)->recordInvoicePayment($organization, $invoice);
        }

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $subscription = $payload['data']['object'];
        $organization = Organization::where('stripe_id', $subscription['customer'] ?? '')->first();

        if ($organization && in_array($subscription['status'] ?? '', ['active', 'trialing'], true)) {
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
            $plan = app(BillingService::class)->planFromPriceId($priceId) ?? 'starter';
            app(BillingService::class)->applyPlan($organization, $plan);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function findOrganization(array $session): ?Organization
    {
        if (! empty($session['metadata']['organization_id'])) {
            return Organization::find($session['metadata']['organization_id']);
        }

        if (! empty($session['client_reference_id'])) {
            return Organization::find($session['client_reference_id']);
        }

        if (! empty($session['customer'])) {
            return Organization::where('stripe_id', $session['customer'])->first();
        }

        return null;
    }

    protected function getUserByStripeId($stripeId)
    {
        return Organization::where('stripe_id', $stripeId)->first();
    }

    protected function newSubscriptionType(array $payload): string
    {
        return $payload['data']['object']['metadata']['type'] ?? 'default';
    }
}
