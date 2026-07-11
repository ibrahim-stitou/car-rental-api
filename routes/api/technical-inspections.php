<?php

use App\Modules\TechnicalInspection\Controllers\TechnicalInspectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('technical-inspections')->group(function () {
    Route::get('/',                        [TechnicalInspectionController::class, 'index'])->middleware('permission:view-technical-inspection');
    Route::post('/',                       [TechnicalInspectionController::class, 'store'])->middleware('permission:create-technical-inspection');
    Route::get('/expired',                 [TechnicalInspectionController::class, 'expired'])->middleware('permission:view-technical-inspection');
    Route::get('/expiring-soon',           [TechnicalInspectionController::class, 'expiringSoon'])->middleware('permission:view-technical-inspection');
    Route::get('/{id}',                    [TechnicalInspectionController::class, 'show'])->middleware('permission:view-technical-inspection');
    Route::put('/{id}',                    [TechnicalInspectionController::class, 'update'])->middleware('permission:edit-technical-inspection');
    Route::delete('/{id}',                 [TechnicalInspectionController::class, 'destroy'])->middleware('permission:delete-technical-inspection');
    Route::post('/{id}/report',            [TechnicalInspectionController::class, 'uploadReport'])->middleware('permission:edit-technical-inspection');
    Route::post('/{id}/photos',            [TechnicalInspectionController::class, 'uploadPhotos'])->middleware('permission:edit-technical-inspection');
    Route::post('/{id}/documents',         [TechnicalInspectionController::class, 'uploadDocuments'])->middleware('permission:edit-technical-inspection');
    Route::get('/{id}/media',              [TechnicalInspectionController::class, 'getMedia'])->middleware('permission:view-technical-inspection');
    Route::delete('/{id}/media/{mediaId}', [TechnicalInspectionController::class, 'deleteMedia'])->middleware('permission:edit-technical-inspection');
});
