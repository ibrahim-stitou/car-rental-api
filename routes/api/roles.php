<?php

use App\Modules\Role\Controllers\RoleController;
use App\Modules\Role\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('roles')->group(function () {
    Route::get('/',                    [RoleController::class, 'index'])->middleware('permission:view-role');
    Route::post('/',                   [RoleController::class, 'store'])->middleware('permission:create-role');
    Route::get('/{id}',                [RoleController::class, 'show'])->middleware('permission:view-role');
    Route::put('/{id}',                [RoleController::class, 'update'])->middleware('permission:edit-role');
    Route::delete('/{id}',             [RoleController::class, 'destroy'])->middleware('permission:delete-role');
    Route::post('/{id}/permissions',   [RoleController::class, 'assignPermissions'])->middleware('permission:assign-permission');
    Route::delete('/{id}/permissions', [RoleController::class, 'revokePermissions'])->middleware('permission:assign-permission');
    Route::get('/{id}/users',          [RoleController::class, 'users'])->middleware('permission:view-role');
    Route::post('/{id}/users',         [RoleController::class, 'attachUser'])->middleware('permission:manage-user-roles');
    Route::delete('/{id}/users/{userId}', [RoleController::class, 'detachUser'])->middleware('permission:manage-user-roles');
});

Route::middleware('auth:api')->prefix('permissions')->group(function () {
    // Read access is open to any authenticated user: the catalog (name/label/module) is
    // low-sensitivity metadata, and the /unauthorized page needs it to translate a missing
    // permission code into a friendly label for users who don't hold view-role themselves.
    Route::get('/',         [PermissionController::class, 'index']);
    Route::post('/',       [PermissionController::class, 'store'])->middleware('permission:assign-permission');
    Route::delete('/{id}', [PermissionController::class, 'destroy'])->middleware('permission:assign-permission');
});
