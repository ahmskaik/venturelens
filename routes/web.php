<?php

use App\Http\Controllers\ImpactController;
use App\Http\Controllers\Admin\AiOperationsController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Applicant\ApplicationController as ApplicantApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::get('/health', HealthController::class)->name('health');

Route::get('/impact', ImpactController::class)->name('impact');

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('apply/{slug}')->name('apply.')->group(function () {
    Route::get('/', [ApplicantApplicationController::class, 'showApplyForm'])->name('form');
    Route::post('/', [ApplicantApplicationController::class, 'store'])->name('submit');
    Route::get('/status/{token}', [ApplicantApplicationController::class, 'status'])->name('status');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/programs/{program}/applications', [AdminApplicationController::class, 'index'])
        ->name('programs.applications.index');

    Route::get('/applications/{application}', [AdminApplicationController::class, 'show'])
        ->name('applications.show');

    Route::post('/applications/{application}/rescreen', [AdminApplicationController::class, 'rescreen'])
        ->name('applications.rescreen');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

    Route::get('/ai-operations', [AiOperationsController::class, 'index'])->name('ai-operations.index');
    Route::post('/ai-operations/support', [AiOperationsController::class, 'storeSupportRequest'])->name('ai-operations.support');
});
