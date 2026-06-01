<?php

use App\Modules\Reservation\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('reservations')->group(function () {
    Route::get('/{id}/contract/pdf',     [ReservationController::class, 'contractPdf']);
    Route::get('/{id}/contract/pdf/save',[ReservationController::class, 'saveContractPdf']);
    Route::get('/',                        [ReservationController::class, 'index']);
    Route::post('/',                       [ReservationController::class, 'store'])->middleware('permission:create-reservation');
    Route::get('/calendar',                [ReservationController::class, 'calendar']);
    Route::get('/overdue',                 [ReservationController::class, 'overdue']);
    Route::get('/statistics',              [ReservationController::class, 'statistics']);
    Route::get('/{id}',                    [ReservationController::class, 'show']);
    Route::put('/{id}',                    [ReservationController::class, 'update'])->middleware('permission:edit-reservation');
    Route::delete('/{id}',                 [ReservationController::class, 'destroy'])->middleware('permission:delete-reservation');
    Route::patch('/{id}/confirm',          [ReservationController::class, 'confirm']);
    Route::patch('/{id}/activate',         [ReservationController::class, 'activate']);
    Route::patch('/{id}/complete',         [ReservationController::class, 'complete']);
    Route::patch('/{id}/cancel',           [ReservationController::class, 'cancel']);
    Route::post('/{id}/contract',          [ReservationController::class, 'uploadContract']);
    Route::post('/{id}/pickup-photos',     [ReservationController::class, 'uploadPickupPhotos']);
    Route::post('/{id}/return-photos',     [ReservationController::class, 'uploadReturnPhotos']);
    Route::post('/{id}/damage-report',     [ReservationController::class, 'uploadDamageReport']);
    Route::delete('/{id}/media/{mediaId}', [ReservationController::class, 'deleteMedia']);
    Route::get('/{id}/invoice',            [ReservationController::class, 'generateInvoice']);
    Route::post('/{id}/restore',           [ReservationController::class, 'restore']);
});

