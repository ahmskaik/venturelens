<?php

use App\Jobs\RunFinanceAgentJob;
use App\Jobs\RunGrowthAgentJob;
use App\Jobs\RunOnboardingAgentJob;
use App\Jobs\RunSupportAgentJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('agents:run-growth', function () {
    RunGrowthAgentJob::dispatch();
    $this->info('Growth agent job dispatched.');
})->purpose('Run the Growth Agent (A1)');

Artisan::command('agents:run-onboarding', function () {
    RunOnboardingAgentJob::dispatch(
        organizationId: 0,
    );
    $this->info('Onboarding agent batch job dispatched.');
})->purpose('Run onboarding for orgs missing programs');

Artisan::command('agents:run-support', function () {
    RunSupportAgentJob::dispatch();
    $this->info('Support agent job dispatched.');
})->purpose('Run the Support Agent (A3)');

Artisan::command('agents:run-finance', function () {
    RunFinanceAgentJob::dispatch();
    $this->info('Finance agent job dispatched.');
})->purpose('Backfill Finance Agent logs for unlogged Stripe charges');

Schedule::job(new RunGrowthAgentJob)->dailyAt('09:00');
Schedule::job(new RunOnboardingAgentJob(0))->dailyAt('08:00');
Schedule::job(new RunSupportAgentJob)->hourly();
Schedule::command('billing:reset-monthly-quotas')->monthlyOn(1, '00:05');
Schedule::command('impact:snapshot')->dailyAt('23:55');
