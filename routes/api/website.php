<?php

use App\Http\Controllers\Api\PublicController;
use App\Modules\Website\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

// ─── Public (no authentication required) ────────────────────────────────────
Route::prefix('public')->group(function () {
    Route::get('/company',                    [PublicController::class, 'company']);
    Route::get('/vehicles',                   [PublicController::class, 'vehicles']);
    Route::get('/vehicles/{id}',              [PublicController::class, 'vehicle']);
    Route::post('/reservations',              [PublicController::class, 'submitReservation']);
});

// ─── Admin (authentication required) ────────────────────────────────────────
Route::middleware('auth:api')->prefix('website')->group(function () {
    Route::get('/stats',                                [WebsiteController::class, 'stats']);

    // Demandes de réservation publiques
    Route::get('/reservations',                         [WebsiteController::class, 'reservations']);
    Route::get('/reservations/{id}',                    [WebsiteController::class, 'showReservation']);
    Route::patch('/reservations/{id}/approve',          [WebsiteController::class, 'approveReservation']);
    Route::patch('/reservations/{id}/reject',           [WebsiteController::class, 'rejectReservation']);

    // Gestion des véhicules sur le site
    Route::get('/vehicles',                             [WebsiteController::class, 'vehicles']);
    Route::patch('/vehicles/{id}',                      [WebsiteController::class, 'updateVehicle']);
});
