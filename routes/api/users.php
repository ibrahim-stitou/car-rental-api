<?php

use App\Modules\User\Controllers\UserController;
use App\Modules\User\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:super-admin|admin'])->prefix('users')->group(function () {
    Route::get('/',                     [UserController::class, 'index']);
    Route::post('/',                    [UserController::class, 'store']);
    Route::get('/{id}',                 [UserController::class, 'show']);
    Route::put('/{id}',                 [UserController::class, 'update']);
    Route::delete('/{id}',              [UserController::class, 'destroy']);
    Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive']);
    Route::post('/{id}/avatar',         [UserController::class, 'uploadAvatar']);
    Route::post('/{id}/assign-role',    [UserController::class, 'assignRole']);
    Route::delete('/{id}/remove-role',  [UserController::class, 'removeRole']);
    Route::get('/{id}/permissions',     [UserController::class, 'permissions']);
    Route::get('/{id}/activity-logs',   [UserController::class, 'activityLogs']);
    Route::post('/{id}/restore',        [UserController::class, 'restore']);
});

Route::middleware('auth:api')->prefix('profile')->group(function () {
    Route::get('/',         [ProfileController::class, 'show']);
    Route::put('/',         [ProfileController::class, 'update']);
    Route::put('/password', [ProfileController::class, 'changePassword']);
    Route::post('/avatar',  [ProfileController::class, 'uploadAvatar']);
});

