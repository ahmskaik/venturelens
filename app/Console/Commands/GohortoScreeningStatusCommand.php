<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GohortoScreeningStatusCommand extends Command
{
    protected $signature = 'gohorto:screening-status
        {--program= : Filter by program slug}
        {--watch : Poll every 30s until queue empty}';

    protected $description = 'Report Gohorto import screening progress (submitted vs screened vs queue)';

    public function handle(): int
    {
        do {
            $this->report();
            if (! $this->option('watch')) {
                break;
            }
            $pending = DB::table('jobs')->count();
            if ($pending === 0) {
                $this->info('Queue empty.');
                break;
            }
            sleep(30);
        } while (true);

        return self::SUCCESS;
    }

    private function report(): void
    {
        $query = Application::query()
            ->where('form_data->integration->source', 'gohorto');

        if ($slug = $this->option('program')) {
            $query->whereHas('program', fn ($q) => $q->where('slug', $slug));
        }

        $total = (clone $query)->count();
        $submitted = (clone $query)->where('status', 'submitted')->count();
        $processing = (clone $query)->where('status', 'processing')->count();
        $screened = (clone $query)->whereIn('status', ['screened', 'needs_info', 'shortlisted', 'accepted', 'rejected', 'waitlisted'])->count();
        $queueJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Gohorto applications', $total],
                ['Submitted (waiting)', $submitted],
                ['Processing', $processing],
                ['Screened / decided', $screened],
                ['Queue jobs pending', $queueJobs],
                ['Failed jobs', $failedJobs],
            ]
        );
    }
}
