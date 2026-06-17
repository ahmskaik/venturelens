<?php

/**
 * Simulates a successful Cohort checkout for an arms-length org (Gmail + neutral org name).
 * Use after Stripe test payment, or standalone to verify classifier + BillingService + /impact.
 *
 * Usage: php scripts/verify-arms-length-checkout.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Models\User;
use App\Services\BillingService;
use App\Services\CompetitionMetrics;
use App\Services\RevenueClassifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$email = 'judge.pilot.'.Str::lower(Str::random(4)).'@gmail.com';
$orgName = 'Pacific Innovation Lab';

$user = User::create([
    'name' => 'Judge Pilot',
    'email' => $email,
    'password' => Hash::make('test-password-not-for-prod'),
]);

$organization = Organization::create([
    'name' => $orgName,
    'slug' => Str::slug($orgName).'-'.Str::lower(Str::random(4)),
    'country_code' => 'US',
    'website' => 'https://pacific-innovation-lab.example',
    'plan' => 'free',
    'screenings_quota' => 5,
    'screenings_used' => 0,
]);

$organization->users()->attach($user->id, ['role' => 'owner']);

$classifier = app(RevenueClassifier::class);
$revenueType = $classifier->classify($organization, $user);

if ($revenueType !== 'arms_length') {
    fwrite(STDERR, "FAIL: expected arms_length, got {$revenueType}\n");
    fwrite(STDERR, "  slug={$organization->slug} website={$organization->website} email={$user->email}\n");
    exit(1);
}

$sessionId = 'cs_verify_arms_'.Str::lower(Str::random(8));
$session = [
    'id' => $sessionId,
    'payment_status' => 'paid',
    'amount_total' => 19900,
    'currency' => 'usd',
    'mode' => 'payment',
    'metadata' => [
        'plan' => 'cohort',
        'revenue_type' => 'arms_length',
        'organization_id' => (string) $organization->id,
    ],
    'customer_details' => ['email' => $email],
];

$charge = app(BillingService::class)->fulfillCheckoutSession($organization, $session, $user);

if (! $charge || $charge->revenue_type !== 'arms_length') {
    fwrite(STDERR, "FAIL: charge not recorded as arms_length\n");
    exit(1);
}

$metrics = app(CompetitionMetrics::class);
$metrics->forget();
$metrics = $metrics->all();
$armsUsd = $metrics['business']['arms_length_revenue_usd'] ?? 0;

echo "OK arms-length verification\n";
echo "  user: {$email}\n";
echo "  org slug: {$organization->slug}\n";
echo "  charge id: {$charge->id} \${$charge->amountUsd()} {$charge->revenue_type}\n";
echo "  impact arms_length_revenue_usd: {$armsUsd}\n";

exit($armsUsd > 0 ? 0 : 1);
