<?php

use App\Modules\Vehicle\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('vehicles')->group(function () {
    Route::get('/',                          [VehicleController::class, 'index']);
    Route::post('/',                         [VehicleController::class, 'store'])->middleware('permission:create-vehicle');
    Route::get('/{id}',                      [VehicleController::class, 'show']);
    Route::put('/{id}',                      [VehicleController::class, 'update'])->middleware('permission:edit-vehicle');
    Route::delete('/{id}',                   [VehicleController::class, 'destroy'])->middleware('permission:delete-vehicle');
    Route::post('/{id}/photos',              [VehicleController::class, 'uploadPhotos']);
    Route::post('/{id}/registration-card',   [VehicleController::class, 'uploadRegistrationCard']);
    Route::post('/{id}/documents',           [VehicleController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}',   [VehicleController::class, 'deleteMedia']);
    Route::get('/{id}/media',                [VehicleController::class, 'getMedia']);
    Route::patch('/{id}/status',             [VehicleController::class, 'updateStatus']);
    Route::get('/{id}/history',              [VehicleController::class, 'history']);
    Route::get('/{id}/reservations',         [VehicleController::class, 'reservations']);
    Route::get('/{id}/statistics',           [VehicleController::class, 'statistics']);
    Route::get('/{id}/expenses',             [VehicleController::class, 'expenses']);
    Route::post('/{id}/restore',             [VehicleController::class, 'restore']);
});

