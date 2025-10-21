<?php

use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Customer\CustomerPlanController;
use App\Http\Controllers\Api\Customer\CustomerCartController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 - Customer Routes
|--------------------------------------------------------------------------
|
| Rotas para usuários comuns (customers)
| Prefixo: /api/v1/customer/*
| Middleware: auth:sanctum
|
*/

Route::prefix('customer')->middleware('auth:sanctum')->group(function () {
    
    // Dados do usuário autenticado
    Route::get('/me', [CustomerController::class, 'show']);
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
    Route::post('/change-password', [CustomerController::class, 'changePassword']);
    
    // Rede e estatísticas do usuário
    Route::get('/network', [CustomerController::class, 'network']);
    Route::get('/sponsor', [CustomerController::class, 'sponsor']);
    Route::get('/statistics', [CustomerController::class, 'statistics']);
    
    // Usuários específicos (com verificação de permissão)
    Route::get('/users/{uuid}/network', [CustomerController::class, 'userNetwork']);
    Route::get('/users/{uuid}/sponsor', [CustomerController::class, 'userSponsor']);
    Route::get('/users/{uuid}/statistics', [CustomerController::class, 'userStatistics']);
    
 
    
    // Carrinho (1 item não pago por usuário)
    Route::post('/cart/add', [CustomerCartController::class, 'addToCart']);
    Route::get('/cart', [CustomerCartController::class, 'viewCart']);
    Route::delete('/cart/remove', [CustomerCartController::class, 'removeFromCart']);
    Route::delete('/cart/clear', [CustomerCartController::class, 'clearCart']);
    Route::post('/cart/checkout', [CustomerCartController::class, 'checkout']);
    

    Route::post('/raffles/{uuid}/tickets', [\App\Http\Controllers\Api\Customer\CustomerRaffleTicketController::class, 'applyTickets']);
    Route::get('/raffles/{uuid}/my-tickets', [\App\Http\Controllers\Api\Customer\CustomerRaffleTicketController::class, 'myTickets']);
    Route::delete('/raffles/{uuid}/tickets', [\App\Http\Controllers\Api\Customer\CustomerRaffleTicketController::class, 'cancelTickets']);
});
