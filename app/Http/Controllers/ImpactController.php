<?php

namespace App\Http\Controllers;

use App\Services\CompetitionMetrics;
use Inertia\Inertia;
use Inertia\Response;

class ImpactController extends Controller
{
    public function __invoke(CompetitionMetrics $metrics): Response
    {
        return Inertia::render('Impact/Index', [
            'metrics' => $metrics->all(),
        ]);
    }
}
