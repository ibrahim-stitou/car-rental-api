<?php

use App\Modules\Claim\Controllers\ClaimController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('claims')->group(function () {
    Route::get('/',                             [ClaimController::class, 'index']);
    Route::post('/',                            [ClaimController::class, 'store']);
    Route::get('/statistics',                   [ClaimController::class, 'statistics']);
    Route::get('/{id}',                         [ClaimController::class, 'show']);
    Route::put('/{id}',                         [ClaimController::class, 'update']);
    Route::delete('/{id}',                      [ClaimController::class, 'destroy']);
    Route::patch('/{id}/status',                [ClaimController::class, 'updateStatus']);
    Route::post('/{id}/photos',                 [ClaimController::class, 'uploadPhotos']);
    Route::post('/{id}/documents',              [ClaimController::class, 'uploadDocuments']);
    Route::post('/{id}/insurance-documents',    [ClaimController::class, 'uploadInsuranceDocuments']);
    Route::delete('/{id}/media/{mediaId}',      [ClaimController::class, 'deleteMedia']);
});
