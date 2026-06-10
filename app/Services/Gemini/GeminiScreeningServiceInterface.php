<?php

namespace App\Services\Gemini;

use App\Models\Application;
use App\Models\Rubric;
use App\Models\ScreeningResult;

interface GeminiScreeningServiceInterface
{
    public function screenApplication(Application $application, Rubric $rubric, string $documentsSummary = ''): ScreeningResult;
}
