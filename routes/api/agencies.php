<?php

use App\Modules\Agency\Controllers\AgencyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:super-admin|admin'])->prefix('agencies')->group(function () {
    Route::get('/',                        [AgencyController::class, 'index']);
    Route::post('/',                       [AgencyController::class, 'store']);
    Route::get('/{id}',                    [AgencyController::class, 'show']);
    Route::put('/{id}',                    [AgencyController::class, 'update']);
    Route::delete('/{id}',                 [AgencyController::class, 'destroy']);
    Route::post('/{id}/logo',              [AgencyController::class, 'uploadLogo']);
    Route::post('/{id}/documents',         [AgencyController::class, 'uploadDocuments']);
    Route::delete('/{id}/media/{mediaId}', [AgencyController::class, 'deleteMedia']);
    Route::get('/{id}/vehicles',           [AgencyController::class, 'vehicles']);
    Route::post('/{id}/restore',           [AgencyController::class, 'restore']);
});

