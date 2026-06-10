<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

class StripePriceResolver
{
    /** @var array<string, array{name: string, amount: int, interval: ?string}> */
    private const PLANS = [
        'cohort' => [
            'name' => 'VentureLens Cohort Package',
            'amount' => 19900,
            'interval' => null,
        ],
        'starter' => [
            'name' => 'VentureLens Starter',
            'amount' => 29900,
            'interval' => 'month',
        ],
    ];

    public function resolve(string $plan): ?string
    {
        $configured = config("venturelens.stripe.prices.{$plan}");
        if (! empty($configured)) {
            return $configured;
        }

        if (! $this->hasStripeSecret()) {
            return null;
        }

        return Cache::remember("venturelens.stripe_price.{$plan}", 3600, function () use ($plan) {
            return $this->findExistingPrice($plan);
        });
    }

    /**
     * @return array<string, string|null>
     */
    public function status(): array
    {
        $status = [
            'secret_configured' => $this->hasStripeSecret(),
            'cohort' => $this->resolve('cohort'),
            'starter' => $this->resolve('starter'),
        ];

        $status['ready'] = $status['secret_configured']
            && $status['cohort']
            && $status['starter'];

        return $status;
    }

    /**
     * Create Stripe products/prices with venturelens_plan metadata.
     *
     * @return array<string, string>
     */
    public function ensurePricesExist(): array
    {
        if (! $this->hasStripeSecret()) {
            throw new \RuntimeException('STRIPE_SECRET is not configured in .env');
        }

        $created = [];

        foreach (self::PLANS as $plan => $definition) {
            $existing = $this->findExistingPrice($plan, fresh: true);
            if ($existing) {
                $created[$plan] = $existing;
                continue;
            }

            $product = Cashier::stripe()->products->create([
                'name' => $definition['name'],
                'metadata' => ['venturelens_plan' => $plan],
            ]);

            $priceData = [
                'product' => $product->id,
                'unit_amount' => $definition['amount'],
                'currency' => 'usd',
                'metadata' => ['venturelens_plan' => $plan],
            ];

            if ($definition['interval']) {
                $priceData['recurring'] = ['interval' => $definition['interval']];
            }

            $price = Cashier::stripe()->prices->create($priceData);
            $created[$plan] = $price->id;

            Cache::forget("venturelens.stripe_price.{$plan}");
        }

        return $created;
    }

    private function findExistingPrice(string $plan, bool $fresh = false): ?string
    {
        if (! $fresh) {
            $cached = Cache::get("venturelens.stripe_price.{$plan}");
            if ($cached) {
                return $cached;
            }
        }

        try {
            $prices = Cashier::stripe()->prices->all(['active' => true, 'limit' => 100]);

            foreach ($prices->data as $price) {
                $metaPlan = $price->metadata['venturelens_plan'] ?? null;
                if ($metaPlan === $plan) {
                    return $price->id;
                }
            }
        } catch (ApiErrorException) {
            return null;
        }

        return null;
    }

    private function hasStripeSecret(): bool
    {
        return ! empty(config('cashier.secret')) || ! empty(env('STRIPE_SECRET'));
    }
}
