<?php

use App\Modules\Client\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('clients')->group(function () {
    Route::get('/',                        [ClientController::class, 'index'])->middleware('permission:view-client');
    Route::post('/',                       [ClientController::class, 'store'])->middleware('permission:create-client');
    Route::get('/blacklisted',             [ClientController::class, 'blacklisted'])->middleware('permission:view-client');
    Route::get('/{id}',                    [ClientController::class, 'show'])->middleware('permission:view-client');
    Route::put('/{id}',                    [ClientController::class, 'update'])->middleware('permission:edit-client');
    Route::delete('/{id}',                 [ClientController::class, 'destroy'])->middleware('permission:delete-client');
    Route::patch('/{id}/blacklist',        [ClientController::class, 'blacklist'])->middleware('permission:blacklist-client');
    Route::patch('/{id}/unblacklist',      [ClientController::class, 'unblacklist'])->middleware('permission:blacklist-client');
    Route::post('/{id}/id-document',       [ClientController::class, 'uploadIdDocument'])->middleware('permission:edit-client');
    Route::post('/{id}/driving-license',   [ClientController::class, 'uploadDrivingLicense'])->middleware('permission:edit-client');
    Route::post('/{id}/selfie',            [ClientController::class, 'uploadSelfie'])->middleware('permission:edit-client');
    Route::post('/{id}/documents',         [ClientController::class, 'uploadDocuments'])->middleware('permission:edit-client');
    Route::delete('/{id}/media/{mediaId}', [ClientController::class, 'deleteMedia'])->middleware('permission:edit-client');
    Route::get('/{id}/reservations',       [ClientController::class, 'reservations'])->middleware('permission:view-client');
    Route::get('/{id}/statistics',         [ClientController::class, 'statistics'])->middleware('permission:view-client');
    Route::post('/{id}/restore',           [ClientController::class, 'restore'])->middleware('permission:edit-client');
});
