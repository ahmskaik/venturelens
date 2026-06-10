<?php

namespace App\Console\Commands;

use App\Services\CompetitionMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SnapshotImpactMetricsCommand extends Command
{
    protected $signature = 'impact:snapshot';

    protected $description = 'Snapshot CompetitionMetrics to docs/evidence/impact-YYYYMMDD.json';

    public function handle(CompetitionMetrics $metrics): int
    {
        $metrics->forget();
        $data = $metrics->all();

        $dir = base_path('docs/evidence');
        File::ensureDirectoryExists($dir);

        $filename = 'impact-'.now()->format('Ymd').'.json';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->info("Impact snapshot written to docs/evidence/{$filename}");

        return self::SUCCESS;
    }
}
