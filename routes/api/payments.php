<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('reservations/{reservationId}/payments')->group(function () {
    Route::get('/',       [PaymentController::class, 'index']);
    Route::post('/',      [PaymentController::class, 'store']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
});