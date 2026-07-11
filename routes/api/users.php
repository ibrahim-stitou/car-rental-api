<?php

use App\Modules\User\Controllers\UserController;
use App\Modules\User\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('users')->group(function () {
    Route::get('/',                     [UserController::class, 'index'])->middleware('permission:view-user');
    Route::post('/',                    [UserController::class, 'store'])->middleware('permission:create-user');
    Route::get('/{id}',                 [UserController::class, 'show'])->middleware('permission:view-user');
    Route::put('/{id}',                 [UserController::class, 'update'])->middleware('permission:edit-user');
    Route::delete('/{id}',              [UserController::class, 'destroy'])->middleware('permission:delete-user');
    Route::patch('/{id}/toggle-active', [UserController::class, 'toggleActive'])->middleware('permission:edit-user');
    Route::post('/{id}/activate',       [UserController::class, 'activate'])->middleware('permission:edit-user');
    Route::post('/{id}/suspend',        [UserController::class, 'suspend'])->middleware('permission:edit-user');
    Route::post('/{id}/avatar',         [UserController::class, 'uploadAvatar'])->middleware('permission:edit-user');
    Route::post('/{id}/assign-role',    [UserController::class, 'assignRole'])->middleware('permission:manage-user-roles');
    Route::delete('/{id}/remove-role',  [UserController::class, 'removeRole'])->middleware('permission:manage-user-roles');
    Route::get('/{id}/permissions',     [UserController::class, 'permissions'])->middleware('permission:view-user');
    Route::get('/{id}/activity-logs',   [UserController::class, 'activityLogs'])->middleware('permission:view-user');
    Route::post('/{id}/restore',        [UserController::class, 'restore'])->middleware('permission:edit-user');
});

Route::middleware('auth:api')->prefix('profile')->group(function () {
    Route::get('/',              [ProfileController::class, 'show']);
    Route::put('/',              [ProfileController::class, 'update']);
    Route::put('/password',      [ProfileController::class, 'changePassword']);
    Route::post('/avatar',       [ProfileController::class, 'uploadAvatar']);
    Route::post('/signature',    [ProfileController::class, 'uploadSignature']);
    Route::post('/stamp',        [ProfileController::class, 'uploadStamp']);
    Route::delete('/signature',  [ProfileController::class, 'deleteSignature']);
    Route::delete('/stamp',      [ProfileController::class, 'deleteStamp']);
});
