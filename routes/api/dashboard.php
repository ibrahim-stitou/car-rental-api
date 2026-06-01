<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('dashboard')->group(function () {
    Route::get('/statistics', [DashboardController::class, 'statistics']);
});
