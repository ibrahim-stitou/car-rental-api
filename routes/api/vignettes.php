<?php

use App\Modules\Vignette\Controllers\VignetteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('vignettes')->group(function () {
    Route::get('/',                        [VignetteController::class, 'index']);
    Route::post('/',                       [VignetteController::class, 'store']);
    Route::get('/expired',                 [VignetteController::class, 'expired']);
    Route::get('/unpaid',                  [VignetteController::class, 'unpaid']);
    Route::get('/{id}',                    [VignetteController::class, 'show']);
    Route::put('/{id}',                    [VignetteController::class, 'update']);
    Route::delete('/{id}',                 [VignetteController::class, 'destroy']);
    Route::patch('/{id}/mark-paid',        [VignetteController::class, 'markAsPaid']);
    Route::post('/{id}/document',          [VignetteController::class, 'uploadDocument']);
    Route::post('/{id}/payment-proof',     [VignetteController::class, 'uploadPaymentProof']);
    Route::post('/{id}/documents',         [VignetteController::class, 'uploadDocuments']);
    Route::get('/{id}/media',              [VignetteController::class, 'getMedia']);
    Route::delete('/{id}/media/{mediaId}', [VignetteController::class, 'deleteMedia']);
});

