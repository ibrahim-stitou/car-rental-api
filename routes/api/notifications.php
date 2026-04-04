<?php

use App\Modules\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('notifications')->group(function () {
    Route::get('/',                [NotificationController::class, 'index']);
    Route::get('/unread',          [NotificationController::class, 'unread']);
    Route::get('/summary',         [NotificationController::class, 'summary']);
    Route::get('/count',           [NotificationController::class, 'unreadCount']);
    Route::get('/types',           [NotificationController::class, 'types']);
    Route::post('/read-all',       [NotificationController::class, 'markAllAsRead']);
    Route::delete('/read',         [NotificationController::class, 'deleteAllRead']);
    Route::get('/{id}',            [NotificationController::class, 'show']);
    Route::patch('/{id}/read',     [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}',         [NotificationController::class, 'destroy']);

    // Admin : envoi manuel de notifications
    Route::post('/send',           [NotificationController::class, 'send'])->middleware('role:super-admin|admin');
});

