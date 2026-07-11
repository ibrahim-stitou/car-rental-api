<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('reservations/{reservationId}/payments')->group(function () {
    Route::get('/',        [PaymentController::class, 'index'])->middleware('permission:view-reservation');
    Route::post('/',       [PaymentController::class, 'store'])->middleware('permission:edit-reservation');
    Route::delete('/{id}', [PaymentController::class, 'destroy'])->middleware('permission:edit-reservation');
});
