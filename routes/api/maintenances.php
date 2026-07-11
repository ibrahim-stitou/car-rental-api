<?php

use App\Modules\Maintenance\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('maintenances')->group(function () {
    Route::get('/',                        [MaintenanceController::class, 'index'])->middleware('permission:view-maintenance');
    Route::post('/',                       [MaintenanceController::class, 'store'])->middleware('permission:create-maintenance');
    Route::get('/scheduled',               [MaintenanceController::class, 'scheduled'])->middleware('permission:view-maintenance');
    Route::get('/overdue',                 [MaintenanceController::class, 'overdue'])->middleware('permission:view-maintenance');
    Route::get('/{id}',                    [MaintenanceController::class, 'show'])->middleware('permission:view-maintenance');
    Route::put('/{id}',                    [MaintenanceController::class, 'update'])->middleware('permission:edit-maintenance');
    Route::delete('/{id}',                 [MaintenanceController::class, 'destroy'])->middleware('permission:delete-maintenance');
    Route::patch('/{id}/complete',         [MaintenanceController::class, 'complete'])->middleware('permission:edit-maintenance');
    Route::patch('/{id}/cancel',           [MaintenanceController::class, 'cancel'])->middleware('permission:edit-maintenance');
    Route::post('/{id}/invoices',          [MaintenanceController::class, 'uploadInvoices'])->middleware('permission:edit-maintenance');
    Route::post('/{id}/photos-before',     [MaintenanceController::class, 'uploadPhotosBefore'])->middleware('permission:edit-maintenance');
    Route::post('/{id}/photos-after',      [MaintenanceController::class, 'uploadPhotosAfter'])->middleware('permission:edit-maintenance');
    Route::post('/{id}/documents',         [MaintenanceController::class, 'uploadDocuments'])->middleware('permission:edit-maintenance');
    Route::get('/{id}/media',              [MaintenanceController::class, 'getMedia'])->middleware('permission:view-maintenance');
    Route::delete('/{id}/media/{mediaId}', [MaintenanceController::class, 'deleteMedia'])->middleware('permission:edit-maintenance');
    Route::get('/oil-change/alerts',       [MaintenanceController::class, 'oilChangeAlerts'])->middleware('permission:view-maintenance');
});
