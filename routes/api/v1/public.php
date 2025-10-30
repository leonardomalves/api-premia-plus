<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Public\LeadCaptureController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Rotas públicas da API que não requerem autenticação.
| Usadas principalmente para landing pages e webhooks.
|
*/

Route::prefix('public')->group(function (): void {
    // Lead Capture - Landing Page Integration
    Route::prefix('leads')->group(function (): void {
        // Capturar lead da landing page
        Route::post('capture', [LeadCaptureController::class, 'capture'])
            ->middleware(['throttle:lead-capture'])
            ->name('public.leads.capture');

        // Verificar status de um lead
        Route::get('status/{uuid}', [LeadCaptureController::class, 'checkStatus'])
            ->middleware(['throttle:lead-status'])
            ->name('public.leads.status');

        // Descadastrar lead
        Route::delete('unsubscribe/{uuid}', [LeadCaptureController::class, 'unsubscribe'])
            ->middleware(['throttle:lead-unsubscribe'])
            ->name('public.leads.unsubscribe');
    });
});