<?php

use App\Modules\Role\Controllers\RoleController;
use App\Modules\Role\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:super-admin'])->prefix('roles')->group(function () {
    Route::get('/',                    [RoleController::class, 'index']);
    Route::post('/',                   [RoleController::class, 'store']);
    Route::get('/{id}',                [RoleController::class, 'show']);
    Route::put('/{id}',                [RoleController::class, 'update']);
    Route::delete('/{id}',             [RoleController::class, 'destroy']);
    Route::post('/{id}/permissions',   [RoleController::class, 'assignPermissions']);
    Route::delete('/{id}/permissions', [RoleController::class, 'revokePermissions']);
});

Route::middleware(['auth:api', 'role:super-admin'])->prefix('permissions')->group(function () {
    Route::get('/',       [PermissionController::class, 'index']);
    Route::post('/',      [PermissionController::class, 'store']);
    Route::delete('/{id}',[PermissionController::class, 'destroy']);
});

