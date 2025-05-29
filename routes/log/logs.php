<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogsController;

Route::controller(LogsController::class)->group(function () {
    Route::middleware(['secure_header', 'auth:api', 'org_inactive', 'mfa_check'])->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');
    });

    Route::middleware(['secure_header', 'throttle:global'])->group(function () {
        Route::get('/generate-auth-string', 'generateAuthString');
        Route::delete('/delete-old-logs', 'deleteOldLogs')->middleware('log_auth');
    });
});
