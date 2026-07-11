<?php

use App\Modules\Vehicle\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('vehicles')->group(function () {
    Route::get('/',                          [VehicleController::class, 'index'])->middleware('permission:view-vehicle');
    Route::post('/',                         [VehicleController::class, 'store'])->middleware('permission:create-vehicle');
    Route::get('/{id}',                      [VehicleController::class, 'show'])->middleware('permission:view-vehicle');
    Route::put('/{id}',                      [VehicleController::class, 'update'])->middleware('permission:edit-vehicle');
    Route::delete('/{id}',                   [VehicleController::class, 'destroy'])->middleware('permission:delete-vehicle');
    Route::post('/{id}/photos',              [VehicleController::class, 'uploadPhotos'])->middleware('permission:manage-vehicle-documents');
    Route::post('/{id}/registration-card',   [VehicleController::class, 'uploadRegistrationCard'])->middleware('permission:manage-vehicle-documents');
    Route::post('/{id}/documents',           [VehicleController::class, 'uploadDocuments'])->middleware('permission:manage-vehicle-documents');
    Route::delete('/{id}/media/{mediaId}',   [VehicleController::class, 'deleteMedia'])->middleware('permission:manage-vehicle-documents');
    Route::get('/{id}/media',                [VehicleController::class, 'getMedia'])->middleware('permission:view-vehicle');
    Route::patch('/{id}/status',             [VehicleController::class, 'updateStatus'])->middleware('permission:edit-vehicle');
    Route::get('/{id}/history',              [VehicleController::class, 'history'])->middleware('permission:view-vehicle');
    Route::get('/{id}/reservations',         [VehicleController::class, 'reservations'])->middleware('permission:view-vehicle');
    Route::get('/{id}/statistics',           [VehicleController::class, 'statistics'])->middleware('permission:view-vehicle');
    Route::get('/{id}/expenses',             [VehicleController::class, 'expenses'])->middleware('permission:view-vehicle');
    Route::post('/{id}/restore',             [VehicleController::class, 'restore'])->middleware('permission:edit-vehicle');
});
