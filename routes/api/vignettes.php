<?php

use App\Modules\Vignette\Controllers\VignetteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('vignettes')->group(function () {
    Route::get('/',                        [VignetteController::class, 'index'])->middleware('permission:view-vignette');
    Route::post('/',                       [VignetteController::class, 'store'])->middleware('permission:create-vignette');
    Route::get('/expired',                 [VignetteController::class, 'expired'])->middleware('permission:view-vignette');
    Route::get('/unpaid',                  [VignetteController::class, 'unpaid'])->middleware('permission:view-vignette');
    Route::get('/{id}',                    [VignetteController::class, 'show'])->middleware('permission:view-vignette');
    Route::put('/{id}',                    [VignetteController::class, 'update'])->middleware('permission:edit-vignette');
    Route::delete('/{id}',                 [VignetteController::class, 'destroy'])->middleware('permission:delete-vignette');
    Route::patch('/{id}/mark-paid',        [VignetteController::class, 'markAsPaid'])->middleware('permission:edit-vignette');
    Route::post('/{id}/document',          [VignetteController::class, 'uploadDocument'])->middleware('permission:edit-vignette');
    Route::post('/{id}/payment-proof',     [VignetteController::class, 'uploadPaymentProof'])->middleware('permission:edit-vignette');
    Route::post('/{id}/documents',         [VignetteController::class, 'uploadDocuments'])->middleware('permission:edit-vignette');
    Route::get('/{id}/media',              [VignetteController::class, 'getMedia'])->middleware('permission:view-vignette');
    Route::delete('/{id}/media/{mediaId}', [VignetteController::class, 'deleteMedia'])->middleware('permission:edit-vignette');
});
