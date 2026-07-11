<?php

use App\Modules\Billing\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('billing')->group(function () {
    Route::get('/{id}/pdf/view',  [BillingController::class, 'viewPdf'])->middleware('permission:view-billing');
    Route::get('/{id}/pdf/download', [BillingController::class, 'downloadPdf'])->middleware('permission:view-billing');
    Route::post('/{id}/pdf/generate', [BillingController::class, 'generatePdf'])->middleware('permission:edit-billing');
    Route::get('/',                            [BillingController::class, 'index'])->middleware('permission:view-billing');
    Route::get('/datatable',                   [BillingController::class, 'datatable'])->middleware('permission:view-billing');
    Route::post('/',                           [BillingController::class, 'store'])->middleware('permission:create-billing');
    Route::get('/statistics',                  [BillingController::class, 'statistics'])->middleware('permission:view-billing');
    Route::post('/from-reservation/{reservationId}', [BillingController::class, 'createFromReservation'])->middleware('permission:create-billing');
    Route::get('/{id}',                        [BillingController::class, 'show'])->middleware('permission:view-billing');
    Route::put('/{id}',                        [BillingController::class, 'update'])->middleware('permission:edit-billing');
    Route::delete('/{id}',                     [BillingController::class, 'destroy'])->middleware('permission:delete-billing');
    Route::post('/{id}/approve',               [BillingController::class, 'approve'])->middleware('permission:approve-billing');
    Route::post('/{id}/unapprove',             [BillingController::class, 'unapprove'])->middleware('permission:approve-billing');
    Route::get('/{id}/history',                [BillingController::class, 'history'])->middleware('permission:view-billing');
    Route::post('/{id}/mark-paid',             [BillingController::class, 'markAsPaid'])->middleware('permission:edit-billing');
    Route::post('/{id}/pdf',                   [BillingController::class, 'uploadPdf'])->middleware('permission:edit-billing');
    Route::post('/{id}/attachments',           [BillingController::class, 'uploadAttachments'])->middleware('permission:edit-billing');
    Route::delete('/{id}/media/{mediaId}',     [BillingController::class, 'deleteMedia'])->middleware('permission:edit-billing');
    Route::post('/{id}/restore',               [BillingController::class, 'restore'])->middleware('permission:edit-billing');
});
