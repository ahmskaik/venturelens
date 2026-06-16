<?php

use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationDecisionService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$demoEmail = config('venturelens.demo.email');
$user = User::where('email', $demoEmail)->first();

if (! $user) {
    fwrite(STDERR, "Demo user not found: {$demoEmail}\n");
    exit(1);
}

$candidate = Application::query()
    ->where('status', 'screened')
    ->whereNotNull('ai_overall_score')
    ->orderByDesc('ai_overall_score')
    ->first();

if (! $candidate) {
    fwrite(STDERR, "No screened application to accept.\n");
    exit(1);
}

/** @var ApplicationDecisionService $decisions */
$decisions = app(ApplicationDecisionService::class);
$decisions->record($candidate, $user, 'accept');

echo "Accepted application #{$candidate->id} ({$candidate->startup_name})\n";
