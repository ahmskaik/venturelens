<?php

namespace App\Http\Controllers;

use App\Services\CompetitionMetrics;
use App\Services\ImpactEvidenceArchiveService;
use Inertia\Inertia;
use Inertia\Response;

class ImpactController extends Controller
{
    public function __invoke(CompetitionMetrics $metrics, ImpactEvidenceArchiveService $archives): Response
    {
        return Inertia::render('Impact/Index', [
            'metrics' => $metrics->all(),
            'archivedSnapshots' => $archives->list(),
            'gcsBucket' => config('filesystems.disks.gcs.bucket'),
        ]);
    }
}
