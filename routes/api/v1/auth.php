<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Shared\HealthController;
use App\Http\Controllers\Api\Shared\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 - Authentication Routes
|--------------------------------------------------------------------------
|
| Rotas de autenticação (sem prefixo de usuário)
| Acessíveis publicamente ou com autenticação básica
|
*/

Route::group([], function () {
    
    // Autenticação pública
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Autenticação protegida
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
    
    // Health check e teste
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/test', [TestController::class, 'index']);
});
