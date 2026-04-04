<?php

use App\Modules\Maintenance\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('maintenances')->group(function () {
    Route::get('/',                        [MaintenanceController::class, 'index']);
    Route::post('/',                       [MaintenanceController::class, 'store']);
    Route::get('/scheduled',               [MaintenanceController::class, 'scheduled']);
    Route::get('/overdue',                 [MaintenanceController::class, 'overdue']);
    Route::get('/{id}',                    [MaintenanceController::class, 'show']);
    Route::put('/{id}',                    [MaintenanceController::class, 'update']);
    Route::delete('/{id}',                 [MaintenanceController::class, 'destroy']);
    Route::patch('/{id}/complete',         [MaintenanceController::class, 'complete']);
    Route::patch('/{id}/cancel',           [MaintenanceController::class, 'cancel']);
    Route::post('/{id}/invoices',          [MaintenanceController::class, 'uploadInvoices']);
    Route::post('/{id}/photos-before',     [MaintenanceController::class, 'uploadPhotosBefore']);
    Route::post('/{id}/photos-after',      [MaintenanceController::class, 'uploadPhotosAfter']);
    Route::delete('/{id}/media/{mediaId}', [MaintenanceController::class, 'deleteMedia']);
});

