<?php

use App\Modules\Billing\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('billing')->group(function () {
    Route::get('/{id}/pdf/view',  [BillingController::class, 'viewPdf']);
    Route::get('/{id}/pdf/download', [BillingController::class, 'downloadPdf']);
    Route::post('/{id}/pdf/generate', [BillingController::class, 'generatePdf']);
    Route::get('/',                            [BillingController::class, 'index']);
    Route::get('/datatable',                   [BillingController::class, 'datatable']);
    Route::post('/',                           [BillingController::class, 'store'])->middleware('permission:create-billing');
    Route::get('/statistics',                  [BillingController::class, 'statistics']);
    Route::post('/from-reservation/{reservationId}', [BillingController::class, 'createFromReservation']);
    Route::get('/{id}',                        [BillingController::class, 'show']);
    Route::put('/{id}',                        [BillingController::class, 'update'])->middleware('permission:edit-billing');
    Route::delete('/{id}',                     [BillingController::class, 'destroy'])->middleware('permission:delete-billing');
    Route::post('/{id}/approve',               [BillingController::class, 'approve'])->middleware('permission:approve-billing');
    Route::post('/{id}/unapprove',             [BillingController::class, 'unapprove'])->middleware('permission:approve-billing');
    Route::get('/{id}/history',                [BillingController::class, 'history']);
    Route::post('/{id}/mark-paid',             [BillingController::class, 'markAsPaid']);
    Route::post('/{id}/pdf',                   [BillingController::class, 'uploadPdf']);
    Route::post('/{id}/attachments',           [BillingController::class, 'uploadAttachments']);
    Route::delete('/{id}/media/{mediaId}',     [BillingController::class, 'deleteMedia']);
    Route::post('/{id}/restore',               [BillingController::class, 'restore']);
});


