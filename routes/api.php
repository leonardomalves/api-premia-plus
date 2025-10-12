<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 - Segmented Routes
Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/customer.php';
    require __DIR__.'/api/v1/administrator.php';
    require __DIR__.'/api/v1/shared.php';
});

// Future versions
// Route::prefix('v2')->group(function () {
//     require __DIR__.'/api/v2/auth.php';
//     require __DIR__.'/api/v2/customer.php';
//     require __DIR__.'/api/v2/administrator.php';
//     require __DIR__.'/api/v2/shared.php';
// });