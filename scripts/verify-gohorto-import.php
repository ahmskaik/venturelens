<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$file = base_path('data/imports/gohorto-project-profiles-2026-06-19-500.json');
$payload = json_decode(file_get_contents($file), true);
$profiles = $payload['profiles'] ?? [];

$program = App\Models\Program::where('slug', 'summer-2026')->first();
if (! $program) {
    echo "summer-2026 program missing — run php artisan db:seed\n";
    exit(1);
}

$existing = App\Models\Application::query()
    ->where('program_id', $program->id)
    ->get()
    ->map(fn ($app) => (int) data_get($app->form_data, 'integration.gohorto_project_id'))
    ->filter()
    ->unique()
    ->values()
    ->all();

$jsonIds = collect($profiles)
    ->map(fn ($p) => (int) ($p['id'] ?? $p['project_id'] ?? 0))
    ->filter()
    ->unique()
    ->values();

$missing = $jsonIds->diff($existing);

echo 'JSON profiles: '.$jsonIds->count().PHP_EOL;
echo 'Imported in summer-2026: '.count($existing).PHP_EOL;
echo 'Missing: '.$missing->count().PHP_EOL;
if ($missing->isNotEmpty()) {
    echo 'Missing IDs (first 20): '.$missing->take(20)->implode(', ').PHP_EOL;
    exit(1);
}

echo "OK — all 500 Gohorto profiles present in summer-2026.\n";
