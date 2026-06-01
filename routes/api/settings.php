<?php

use App\Modules\Setting\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('settings')->group(function () {
    Route::get('/',                [SettingController::class, 'index']);
    Route::get('/{group}',         [SettingController::class, 'group']);
    Route::put('/{group}',         [SettingController::class, 'update'])->middleware('permission:manage-settings');
    Route::post('/logo',           [SettingController::class, 'uploadLogo'])->middleware('permission:manage-settings');
    Route::post('/hero-image',     [SettingController::class, 'uploadHeroImage'])->middleware('permission:manage-settings');
});
