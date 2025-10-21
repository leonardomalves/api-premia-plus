<?php

use App\Http\Controllers\Api\Customer\CustomerPlanController;
use App\Http\Controllers\Api\Shared\HealthController;
use App\Http\Controllers\Api\Shared\TestController;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 - Shared Routes
|--------------------------------------------------------------------------
|
| Rotas compartilhadas entre diferentes tipos de usuários
| Prefixo: /api/v1/*
| Middleware: variável por rota
|
*/

Route::group([], function () {
    

        // Rifas e aplicação de tickets
    Route::get('/raffles', [\App\Http\Controllers\Api\Customer\CustomerRaffleTicketController::class, 'index']);
    Route::get('/raffles/{uuid}', [\App\Http\Controllers\Api\Customer\CustomerRaffleTicketController::class, 'show']);

       // Planos (apenas leitura)
    Route::get('/plans', [CustomerPlanController::class, 'index']);
    Route::get('/plans/search', [CustomerPlanController::class, 'search']);
    Route::get('/plans/promotional/list', [CustomerPlanController::class, 'promotional']);
    Route::get('/plans/{uuid}', [CustomerPlanController::class, 'show']);

    // Rotas públicas
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/health/detailed', function (HealthCheckService $healthCheck) {
        return response()->json($healthCheck->check());
    });
    Route::get('/test', [TestController::class, 'index']);
    
    // Rotas com autenticação (qualquer usuário autenticado)
    Route::middleware('auth:sanctum')->group(function () {
        // Métricas e monitoramento (apenas usuários autenticados)
        Route::get('/metrics/user', function (Request $request) {
            $user = $request->user();
            return response()->json([
                'user_id' => $user->id,
                'requests_today' => cache()->get('user_requests_' . $user->id . '_' . today(), 0),
                'last_activity' => $user->updated_at,
            ]);
        });
    });
});
