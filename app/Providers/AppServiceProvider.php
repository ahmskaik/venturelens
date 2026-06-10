<?php

namespace App\Providers;

use App\Models\Organization;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiScreeningService;
use App\Services\Gemini\GeminiScreeningServiceInterface;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Smalot\PdfParser\Parser;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiClient::class, fn () => GeminiClient::fromConfig());

        $this->app->bind(GeminiScreeningServiceInterface::class, GeminiScreeningService::class);

        $this->app->singleton(Parser::class, fn () => new Parser);
    }

    public function boot(): void
    {
        Cashier::useCustomerModel(Organization::class);
        Cashier::$registersRoutes = false;
    }
}
