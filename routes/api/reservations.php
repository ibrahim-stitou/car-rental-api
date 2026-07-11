<?php

use App\Modules\Reservation\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('reservations')->group(function () {
    Route::get('/{id}/contract/pdf',     [ReservationController::class, 'contractPdf'])->middleware('permission:view-reservation');
    Route::get('/{id}/contract/pdf/save',[ReservationController::class, 'saveContractPdf'])->middleware('permission:view-reservation');
    Route::get('/',                        [ReservationController::class, 'index'])->middleware('permission:view-reservation');
    Route::post('/',                       [ReservationController::class, 'store'])->middleware('permission:create-reservation');
    Route::get('/calendar',                [ReservationController::class, 'calendar'])->middleware('permission:view-reservation');
    Route::get('/overdue',                 [ReservationController::class, 'overdue'])->middleware('permission:view-reservation');
    Route::get('/credits',                 [ReservationController::class, 'credits'])->middleware('permission:view-reservation');
    Route::get('/statistics',              [ReservationController::class, 'statistics'])->middleware('permission:view-reservation');
    Route::get('/check-conflict',          [ReservationController::class, 'checkConflict'])->middleware('permission:view-reservation');
    Route::get('/{id}',                    [ReservationController::class, 'show'])->middleware('permission:view-reservation');
    Route::put('/{id}',                    [ReservationController::class, 'update'])->middleware('permission:edit-reservation');
    Route::delete('/{id}',                 [ReservationController::class, 'destroy'])->middleware('permission:delete-reservation');
    Route::patch('/{id}/confirm',          [ReservationController::class, 'confirm'])->middleware('permission:confirm-reservation');
    Route::patch('/{id}/activate',         [ReservationController::class, 'activate'])->middleware('permission:activate-reservation');
    Route::patch('/{id}/complete',         [ReservationController::class, 'complete'])->middleware('permission:complete-reservation');
    Route::patch('/{id}/cancel',           [ReservationController::class, 'cancel'])->middleware('permission:cancel-reservation');
    Route::patch('/{id}/no-show',          [ReservationController::class, 'noShow'])->middleware('permission:cancel-reservation');
    Route::post('/{id}/contract',          [ReservationController::class, 'uploadContract'])->middleware('permission:edit-reservation');
    Route::post('/{id}/pickup-photos',     [ReservationController::class, 'uploadPickupPhotos'])->middleware('permission:edit-reservation');
    Route::post('/{id}/return-photos',     [ReservationController::class, 'uploadReturnPhotos'])->middleware('permission:edit-reservation');
    Route::post('/{id}/damage-report',     [ReservationController::class, 'uploadDamageReport'])->middleware('permission:edit-reservation');
    Route::delete('/{id}/media/{mediaId}', [ReservationController::class, 'deleteMedia'])->middleware('permission:edit-reservation');
    Route::get('/{id}/invoice',            [ReservationController::class, 'generateInvoice'])->middleware('permission:view-reservation');
    Route::post('/{id}/restore',           [ReservationController::class, 'restore'])->middleware('permission:edit-reservation');
    Route::patch('/{id}/extend',           [ReservationController::class, 'extend'])->middleware('permission:edit-reservation');
});
