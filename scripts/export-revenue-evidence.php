<?php

/**
 * Export revenue evidence for judges (JSON + printable HTML).
 * Complements Stripe Dashboard PDF export.
 *
 * Usage: php scripts/export-revenue-evidence.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Services\CompetitionMetrics;

$metrics = app(CompetitionMetrics::class);
$metrics->forget();
$summary = $metrics->all();

$charges = RevenueCharge::query()
    ->with('organization:id,name,slug,country_code')
    ->orderBy('paid_at')
    ->get()
    ->map(fn (RevenueCharge $c) => [
        'id' => $c->id,
        'organization' => $c->organization?->name,
        'org_slug' => $c->organization?->slug,
        'plan' => $c->plan,
        'amount_usd' => round($c->amount_cents / 100, 2),
        'revenue_type' => $c->revenue_type,
        'stripe_checkout_session_id' => $c->stripe_checkout_session_id,
        'stripe_invoice_id' => $c->stripe_invoice_id,
        'paid_at' => $c->paid_at?->toIso8601String(),
    ]);

$payload = [
    'exported_at' => now()->toIso8601String(),
    'source' => 'venturelens.revenue_charges',
    'business_summary' => $summary['business'],
    'charges' => $charges,
    'classification_note' => 'Arms-length vs related-party per RevenueClassifier (email domain + org slug).',
];

$jsonPath = base_path('docs/evidence/revenue-evidence.json');
file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$html = buildHtml($payload);
$htmlPath = base_path('docs/evidence/revenue-evidence.html');
file_put_contents($htmlPath, $html);

echo "Wrote {$jsonPath}\n";
echo "Wrote {$htmlPath}\n";
echo "Print HTML to PDF (Ctrl+P) → docs/evidence/revenue-evidence.pdf\n";

function buildHtml(array $payload): string
{
    $b = $payload['business_summary'];
    $rows = '';
    foreach ($payload['charges'] as $c) {
        $rows .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>$%s</td><td>%s</td></tr>',
            e($c['paid_at'] ? substr($c['paid_at'], 0, 10) : '—'),
            e($c['organization'] ?? '—'),
            e($c['plan']),
            e(number_format($c['amount_usd'], 2)),
            e(str_replace('_', ' ', $c['revenue_type']))
        );
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>VentureLens Revenue Evidence</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; color: #1e293b; }
h1 { font-size: 1.5rem; }
table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; font-size: 0.9rem; }
th, td { border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; text-align: left; }
th { background: #f8fafc; }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.5rem 0; }
.stat { border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; }
.stat strong { display: block; font-size: 1.5rem; color: #059669; }
.note { font-size: 0.85rem; color: #64748b; margin-top: 2rem; }
</style>
</head>
<body>
<h1>VentureLens — Revenue Evidence (Stripe test mode)</h1>
<p>Exported: {$payload['exported_at']}</p>
<div class="stats">
  <div class="stat"><span>Arms-length</span><strong>\${$b['arms_length_revenue_usd']}</strong></div>
  <div class="stat"><span>Related-party</span><strong>\${$b['related_party_revenue_usd']}</strong></div>
  <div class="stat"><span>Paying customers (arms-length)</span><strong>{$b['arms_length_paying_customers']}</strong></div>
</div>
<table>
<thead><tr><th>Date</th><th>Organization</th><th>Plan</th><th>Amount</th><th>Type</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
<p class="note">{$payload['classification_note']} Cross-check with Stripe Dashboard test payments and /billing in the app.</p>
</body>
</html>
HTML;
}
