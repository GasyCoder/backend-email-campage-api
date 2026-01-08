<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\WorkspaceController;
use App\Http\Controllers\Api\V1\PlansController;
use App\Http\Controllers\Api\V1\UsageController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/login', LoginController::class);

    Route::middleware(['auth:sanctum', 'workspace'])->group(function () {
        Route::post('/auth/logout', LogoutController::class);
        Route::get('/me', MeController::class);
        Route::get('/workspace', WorkspaceController::class);

        Route::get('/plans', PlansController::class);
        Route::get('/usage', UsageController::class);
    }); 
});
