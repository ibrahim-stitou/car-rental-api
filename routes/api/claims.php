<?php

use App\Modules\Claim\Controllers\ClaimController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('claims')->group(function () {
    Route::get('/',                             [ClaimController::class, 'index'])->middleware('permission:view-claim');
    Route::post('/',                            [ClaimController::class, 'store'])->middleware('permission:create-claim');
    Route::get('/statistics',                   [ClaimController::class, 'statistics'])->middleware('permission:view-claim');
    Route::get('/{id}',                         [ClaimController::class, 'show'])->middleware('permission:view-claim');
    Route::put('/{id}',                         [ClaimController::class, 'update'])->middleware('permission:edit-claim');
    Route::delete('/{id}',                      [ClaimController::class, 'destroy'])->middleware('permission:delete-claim');
    Route::patch('/{id}/status',                [ClaimController::class, 'updateStatus'])->middleware('permission:edit-claim');
    Route::post('/{id}/photos',                 [ClaimController::class, 'uploadPhotos'])->middleware('permission:manage-claim-documents');
    Route::post('/{id}/documents',              [ClaimController::class, 'uploadDocuments'])->middleware('permission:manage-claim-documents');
    Route::post('/{id}/insurance-documents',    [ClaimController::class, 'uploadInsuranceDocuments'])->middleware('permission:manage-claim-documents');
    Route::delete('/{id}/media/{mediaId}',      [ClaimController::class, 'deleteMedia'])->middleware('permission:manage-claim-documents');
});
