<?php

use App\Modules\Insurance\Controllers\InsuranceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('insurances')->group(function () {
    Route::get('/',                        [InsuranceController::class, 'index'])->middleware('permission:view-insurance');
    Route::post('/',                       [InsuranceController::class, 'store'])->middleware('permission:create-insurance');
    Route::get('/expired',                 [InsuranceController::class, 'expired'])->middleware('permission:view-insurance');
    Route::get('/expiring-soon',           [InsuranceController::class, 'expiringSoon'])->middleware('permission:view-insurance');
    Route::get('/{id}',                    [InsuranceController::class, 'show'])->middleware('permission:view-insurance');
    Route::put('/{id}',                    [InsuranceController::class, 'update'])->middleware('permission:edit-insurance');
    Route::delete('/{id}',                 [InsuranceController::class, 'destroy'])->middleware('permission:delete-insurance');
    Route::post('/{id}/policy-document',   [InsuranceController::class, 'uploadPolicyDocument'])->middleware('permission:edit-insurance');
    Route::post('/{id}/green-card',        [InsuranceController::class, 'uploadGreenCard'])->middleware('permission:edit-insurance');
    Route::post('/{id}/attachments',       [InsuranceController::class, 'uploadAttachments'])->middleware('permission:edit-insurance');
    Route::post('/{id}/documents',         [InsuranceController::class, 'uploadDocuments'])->middleware('permission:edit-insurance');
    Route::delete('/{id}/media/{mediaId}', [InsuranceController::class, 'deleteMedia'])->middleware('permission:edit-insurance');
    Route::get('/{id}/media',              [InsuranceController::class, 'getMedia'])->middleware('permission:view-insurance');
});
