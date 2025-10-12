<?php

use App\Http\Controllers\Api\Administrator\AdministratorController;
use App\Http\Controllers\Api\Administrator\AdministratorPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 - Administrator Routes
|--------------------------------------------------------------------------
|
| Rotas para administradores
| Prefixo: /api/v1/administrator/*
| Middleware: auth:sanctum + admin
|
*/

Route::prefix('administrator')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // Gerenciamento de usuários
    Route::get('/users', [AdministratorController::class, 'index']);
    Route::get('/users/{uuid}', [AdministratorController::class, 'show']);
    Route::post('/users', [AdministratorController::class, 'store']);
    Route::put('/users/{uuid}', [AdministratorController::class, 'update']);
    Route::delete('/users/{uuid}', [AdministratorController::class, 'destroy']);
    
    // Rede e estatísticas de usuários específicos
    Route::get('/users/{uuid}/network', [AdministratorController::class, 'network']);
    Route::get('/users/{uuid}/sponsor', [AdministratorController::class, 'sponsor']);
    Route::get('/users/{uuid}/statistics', [AdministratorController::class, 'statistics']);
    
    // Sistema e dashboard
    Route::get('/statistics', [AdministratorController::class, 'systemStatistics']);
    Route::get('/dashboard', [AdministratorController::class, 'dashboard']);
    
    // Operações em massa
    Route::post('/users/bulk-update', [AdministratorController::class, 'bulkUpdate']);
    Route::post('/users/bulk-delete', [AdministratorController::class, 'bulkDelete']);
    Route::post('/users/export', [AdministratorController::class, 'exportUsers']);
    
    // Gerenciamento de planos (CRUD completo)
    Route::apiResource('plans', AdministratorPlanController::class);
    Route::post('/plans/{uuid}/toggle-status', [AdministratorPlanController::class, 'toggleStatus']);
    Route::get('/plans/statistics/overview', [AdministratorPlanController::class, 'statistics']);
});
