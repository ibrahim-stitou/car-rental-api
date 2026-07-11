<?php

use App\Modules\Parameter\Controllers\ParameterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('parameters')->group(function () {
    // Read access is intentionally open to any authenticated user: parameters are
    // reference/lookup data (dropdown options) consumed by forms across every module,
    // not an admin-only resource. Only mutations are permission-gated.
    Route::get('/',        [ParameterController::class, 'index']);
    Route::get('/{id}',    [ParameterController::class, 'show']);
    Route::post('/',       [ParameterController::class, 'store'])->middleware('permission:manage-settings');
    Route::put('/{id}',    [ParameterController::class, 'update'])->middleware('permission:manage-settings');
    Route::delete('/{id}', [ParameterController::class, 'destroy'])->middleware('permission:manage-settings');
});
