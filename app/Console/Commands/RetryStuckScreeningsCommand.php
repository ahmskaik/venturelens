<?php

namespace App\Console\Commands;

use App\Jobs\ScreenApplicationJob;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetryStuckScreeningsCommand extends Command
{
    protected $signature = 'screening:retry-stuck
        {--program= : Only applications in this program slug}
        {--delay=5 : Seconds between queued jobs}
        {--limit=0 : Max applications to requeue (0 = all)}';

    protected $description = 'Re-queue screening for submitted/processing apps without a successful screening result';

    public function handle(): int
    {
        $query = Application::query()
            ->whereIn('status', ['submitted', 'processing'])
            ->whereDoesntHave('screeningResults', fn ($q) => $q->whereNull('error')->whereNotNull('overall_score'));

        if ($slug = $this->option('program')) {
            $query->whereHas('program', fn ($q) => $q->where('slug', $slug));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $apps = $query->orderBy('id')->get();
        $delay = max(1, (int) $this->option('delay'));
        $queued = 0;

        foreach ($apps as $index => $application) {
            $application->update(['status' => 'submitted']);
            ScreenApplicationJob::dispatch($application->id)
                ->delay(now()->addSeconds($delay * $index));
            $queued++;
        }

        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        $this->info("Re-queued {$queued} screening job(s) with {$delay}s spacing.");
        $this->line("Queue pending: {$pending} · Failed jobs: {$failed}");

        return self::SUCCESS;
    }
}
