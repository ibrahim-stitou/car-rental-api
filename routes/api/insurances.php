<?php

use App\Modules\Insurance\Controllers\InsuranceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('insurances')->group(function () {
    Route::get('/',                        [InsuranceController::class, 'index']);
    Route::post('/',                       [InsuranceController::class, 'store']);
    Route::get('/expired',                 [InsuranceController::class, 'expired']);
    Route::get('/expiring-soon',           [InsuranceController::class, 'expiringSoon']);
    Route::get('/{id}',                    [InsuranceController::class, 'show']);
    Route::put('/{id}',                    [InsuranceController::class, 'update']);
    Route::delete('/{id}',                 [InsuranceController::class, 'destroy']);
    Route::post('/{id}/policy-document',   [InsuranceController::class, 'uploadPolicyDocument']);
    Route::post('/{id}/green-card',        [InsuranceController::class, 'uploadGreenCard']);
    Route::post('/{id}/attachments',       [InsuranceController::class, 'uploadAttachments']);
    Route::delete('/{id}/media/{mediaId}', [InsuranceController::class, 'deleteMedia']);
    Route::get('/{id}/media',              [InsuranceController::class, 'getMedia']);
});

