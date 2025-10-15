<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Group Routes
|--------------------------------------------------------------------------
|
| Agrupamento das rotas da API v1
|
*/

// API v1 - Segmented Routes
Route::prefix('v1')->group(function () {
    require __DIR__.'/auth.php';
    require __DIR__.'/customer.php';
    require __DIR__.'/administrator.php';
    require __DIR__.'/shared.php';
});

