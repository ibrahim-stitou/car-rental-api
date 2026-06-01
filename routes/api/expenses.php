<?php

use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('expenses')->group(function () {
    Route::get('/statistics', [ExpenseController::class, 'statistics']);
    Route::get('/',           [ExpenseController::class, 'index']);
    Route::post('/',          [ExpenseController::class, 'store']);
    Route::get('/{id}',       [ExpenseController::class, 'show']);
    Route::put('/{id}',       [ExpenseController::class, 'update']);
    Route::delete('/{id}',    [ExpenseController::class, 'destroy']);
});