<?php

namespace App\Console\Commands;

use App\Services\StripePriceResolver;
use Illuminate\Console\Command;

class StripeEnsurePricesCommand extends Command
{
    protected $signature = 'stripe:ensure-prices';

    protected $description = 'Create VentureLens Stripe products/prices (test mode) and print .env values';

    public function handle(StripePriceResolver $resolver): int
    {
        try {
            $prices = $resolver->ensurePricesExist();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Stripe prices ready. Add these to your .env:');
        $this->newLine();
        $this->line('STRIPE_PRICE_COHORT='.$prices['cohort']);
        $this->line('STRIPE_PRICE_STARTER='.$prices['starter']);
        $this->newLine();
        $this->comment('Then run: php artisan config:clear');

        return self::SUCCESS;
    }
}
