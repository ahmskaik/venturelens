<?php

use App\Http\Controllers\EvidenceSnapshotController;
use App\Http\Controllers\ImpactController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\AiOperationsController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Applicant\ApplicationController as ApplicantApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredFounderController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Founder\ApplicationController as FounderApplicationController;
use App\Http\Controllers\Founder\DashboardController as FounderDashboardController;
use App\Http\Controllers\Founder\ProgramController as FounderProgramController;
use App\Http\Controllers\Founder\SettingsController as FounderSettingsController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::get('/health', HealthController::class)->name('health');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/impact', ImpactController::class)->name('impact');

Route::get('/evidence/snapshots/{filename}', [EvidenceSnapshotController::class, 'show'])
    ->where('filename', 'impact-\d{8}\.json')
    ->name('evidence.snapshot');

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/founder/register', [RegisteredFounderController::class, 'create'])->name('founder.register');
    Route::post('/founder/register', [RegisteredFounderController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::prefix('apply/{slug}')->name('apply.')->group(function () {
    Route::get('/', [ApplicantApplicationController::class, 'showApplyForm'])->name('form');
    Route::post('/', [ApplicantApplicationController::class, 'store'])->name('submit');
    Route::get('/status/{token}', [ApplicantApplicationController::class, 'status'])->name('status');
});

Route::middleware(['auth', 'incubator'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/cohorts', [ProgramController::class, 'index'])->name('cohorts.index');

    Route::get('/applications', [AdminApplicationController::class, 'organizationIndex'])
        ->name('applications.index');

    Route::get('/programs/{program}/applications', [AdminApplicationController::class, 'index'])
        ->name('programs.applications.index');

    Route::get('/applications/{application}', [AdminApplicationController::class, 'show'])
        ->name('applications.show');

    Route::post('/applications/{application}/rescreen', [AdminApplicationController::class, 'rescreen'])
        ->name('applications.rescreen');

    Route::post('/applications/{application}/decision', [AdminApplicationController::class, 'decision'])
        ->name('applications.decision');

    Route::post('/applications/{application}/communications/{communication}/send', [AdminApplicationController::class, 'sendCommunication'])
        ->name('applications.communications.send');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');

    Route::get('/ai-operations', [AiOperationsController::class, 'index'])->name('ai-operations.index');
    Route::post('/ai-operations/support', [AiOperationsController::class, 'storeSupportRequest'])->name('ai-operations.support');

    Route::get('/ask', [ChatController::class, 'index'])->name('ask.index');
    Route::post('/ask', [ChatController::class, 'store'])->name('ask.store');
    Route::post('/ask/clear', [ChatController::class, 'clear'])->name('ask.clear');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/organization', [SettingsController::class, 'updateOrganization'])->name('settings.organization.update');
});

Route::middleware(['auth', 'founder'])->prefix('founder')->name('founder.')->group(function () {
    Route::get('/dashboard', FounderDashboardController::class)->name('dashboard');
    Route::get('/applications', [FounderApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [FounderApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/edit', [FounderApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('/applications/{application}', [FounderApplicationController::class, 'update'])->name('applications.update');
    Route::get('/programs', [FounderProgramController::class, 'index'])->name('programs.index');
    Route::get('/settings', [FounderSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [FounderSettingsController::class, 'update'])->name('settings.update');
});
