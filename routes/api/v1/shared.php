<?php

use App\Http\Controllers\Api\Shared\HealthController;
use App\Http\Controllers\Api\Shared\TestController;
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
    
    // Rotas públicas
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/test', [TestController::class, 'index']);
    
    // Rotas com autenticação (qualquer usuário autenticado)
    Route::middleware('auth:sanctum')->group(function () {
        // Rotas compartilhadas que qualquer usuário autenticado pode acessar
        // (se necessário no futuro)
    });
});
