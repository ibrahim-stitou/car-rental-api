<?php

use App\Modules\Agency\Controllers\AgencyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('agencies')->group(function () {
    Route::get('/',                        [AgencyController::class, 'index'])->middleware('permission:view-agency');
    Route::post('/',                       [AgencyController::class, 'store'])->middleware('permission:create-agency');
    Route::get('/{id}',                    [AgencyController::class, 'show'])->middleware('permission:view-agency');
    Route::put('/{id}',                    [AgencyController::class, 'update'])->middleware('permission:edit-agency');
    Route::delete('/{id}',                 [AgencyController::class, 'destroy'])->middleware('permission:delete-agency');
    Route::post('/{id}/logo',              [AgencyController::class, 'uploadLogo'])->middleware('permission:edit-agency');
    Route::post('/{id}/documents',         [AgencyController::class, 'uploadDocuments'])->middleware('permission:edit-agency');
    Route::delete('/{id}/media/{mediaId}', [AgencyController::class, 'deleteMedia'])->middleware('permission:edit-agency');
    Route::get('/{id}/vehicles',           [AgencyController::class, 'vehicles'])->middleware('permission:view-agency');
    Route::get('/{id}/statistics',         [AgencyController::class, 'statistics'])->middleware('permission:view-agency');
    Route::get('/{id}/credits',            [AgencyController::class, 'credits'])->middleware('permission:view-agency');
    Route::post('/{id}/restore',           [AgencyController::class, 'restore'])->middleware('permission:edit-agency');
});
