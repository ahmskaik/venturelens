<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$summer = App\Models\Program::where('slug', 'summer-2026')->first();
$demo = App\Models\Organization::where('slug', 'demo-incubator')->first();

echo 'summer-2026 applications: '.App\Models\Application::where('program_id', $summer?->id)->count().PHP_EOL;
echo 'gohorto-tagged: '.App\Models\Application::where('form_data->integration->source', 'gohorto')->count().PHP_EOL;
echo 'demo org programs: '.($demo?->programs()->count() ?? 0).PHP_EOL;
echo 'demo org total apps: '.App\Models\Application::whereIn('program_id', $demo?->programs()->pluck('id') ?? [])->count().PHP_EOL;
echo 'distinct gohorto project ids: '.App\Models\Application::whereNotNull('form_data->integration->gohorto_project_id')->distinct()->count('form_data->integration->gohorto_project_id').PHP_EOL;
