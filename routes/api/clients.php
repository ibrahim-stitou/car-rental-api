<?php

use App\Modules\Client\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('clients')->group(function () {
    Route::get('/',                        [ClientController::class, 'index']);
    Route::post('/',                       [ClientController::class, 'store'])->middleware('permission:create-client');
    Route::get('/blacklisted',             [ClientController::class, 'blacklisted']);
    Route::get('/{id}',                    [ClientController::class, 'show']);
    Route::put('/{id}',                    [ClientController::class, 'update'])->middleware('permission:edit-client');
    Route::delete('/{id}',                 [ClientController::class, 'destroy'])->middleware('permission:delete-client');
    Route::patch('/{id}/blacklist',        [ClientController::class, 'blacklist']);
    Route::patch('/{id}/unblacklist',      [ClientController::class, 'unblacklist']);
    Route::post('/{id}/id-document',       [ClientController::class, 'uploadIdDocument']);
    Route::post('/{id}/driving-license',   [ClientController::class, 'uploadDrivingLicense']);
    Route::post('/{id}/selfie',            [ClientController::class, 'uploadSelfie']);
    Route::post('/{id}/documents',         [ClientController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}', [ClientController::class, 'deleteMedia']);
    Route::get('/{id}/reservations',       [ClientController::class, 'reservations']);
    Route::post('/{id}/restore',           [ClientController::class, 'restore']);
});

