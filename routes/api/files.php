<?php

use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('files')->group(function () {
    Route::post('/upload-temp',          [FileController::class, 'uploadTemp']);
    Route::post('/upload-temp-multiple', [FileController::class, 'uploadTempMultiple']);
    Route::post('/cleanup-temp',         [FileController::class, 'cleanupTemp']);
    Route::get('/temp-preview/{id}',     [FileController::class, 'previewTemp'])->name('files.temp.preview');
});
