<?php

use App\Http\Controllers\Api\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('expenses')->group(function () {
    Route::get('/statistics',            [ExpenseController::class, 'statistics'])->middleware('permission:view-expense');
    Route::get('/',                      [ExpenseController::class, 'index'])->middleware('permission:view-expense');
    Route::post('/',                     [ExpenseController::class, 'store'])->middleware('permission:create-expense');
    Route::get('/{id}',                  [ExpenseController::class, 'show'])->middleware('permission:view-expense');
    Route::put('/{id}',                  [ExpenseController::class, 'update'])->middleware('permission:edit-expense');
    Route::delete('/{id}',               [ExpenseController::class, 'destroy'])->middleware('permission:delete-expense');
    Route::post('/{id}/documents',       [ExpenseController::class, 'uploadDocuments'])->middleware('permission:edit-expense');
    Route::post('/{id}/receipts',        [ExpenseController::class, 'uploadReceipts'])->middleware('permission:edit-expense');
    Route::get('/{id}/media',            [ExpenseController::class, 'getMedia'])->middleware('permission:view-expense');
    Route::delete('/{id}/media/{mediaId}',[ExpenseController::class, 'deleteMedia'])->middleware('permission:edit-expense');
});
