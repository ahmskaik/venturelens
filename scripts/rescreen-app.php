<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 6);
App\Jobs\ScreenApplicationJob::dispatchSync($id);
$a = App\Models\Application::with('latestScreeningResult')->find($id);
echo "Screened app {$id} ({$a->startup_name}): score={$a->latestScreeningResult?->overall_score}\n";
