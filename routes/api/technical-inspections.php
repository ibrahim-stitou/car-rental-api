<?php

use App\Modules\TechnicalInspection\Controllers\TechnicalInspectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('technical-inspections')->group(function () {
    Route::get('/',                        [TechnicalInspectionController::class, 'index']);
    Route::post('/',                       [TechnicalInspectionController::class, 'store']);
    Route::get('/expired',                 [TechnicalInspectionController::class, 'expired']);
    Route::get('/expiring-soon',           [TechnicalInspectionController::class, 'expiringSoon']);
    Route::get('/{id}',                    [TechnicalInspectionController::class, 'show']);
    Route::put('/{id}',                    [TechnicalInspectionController::class, 'update']);
    Route::delete('/{id}',                 [TechnicalInspectionController::class, 'destroy']);
    Route::post('/{id}/report',            [TechnicalInspectionController::class, 'uploadReport']);
    Route::post('/{id}/photos',            [TechnicalInspectionController::class, 'uploadPhotos']);
    Route::delete('/{id}/media/{mediaId}', [TechnicalInspectionController::class, 'deleteMedia']);
});

