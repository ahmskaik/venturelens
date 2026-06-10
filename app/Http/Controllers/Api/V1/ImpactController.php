<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CompetitionMetrics;
use Illuminate\Http\JsonResponse;

class ImpactController extends Controller
{
    public function __invoke(CompetitionMetrics $metrics): JsonResponse
    {
        return response()->json($metrics->all());
    }
}
