<?php

use App\Modules\User\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'permission:view-logs'])->prefix('logs')->group(function () {
    Route::get('/',                    [AuditController::class, 'index']);
    Route::get('/{id}',                [AuditController::class, 'show']);
    Route::get('/model/{type}/{id}',   [AuditController::class, 'byModel']);
    Route::get('/user/{userId}',       [AuditController::class, 'byUser']);
    Route::delete('/{id}',             [AuditController::class, 'destroy'])->middleware('role:super-admin');
});

