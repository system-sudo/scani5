<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::controller(NotificationController::class)->group(function () {
    Route::middleware(['secure_header', 'auth:api', 'org_inactive', 'orgAccess', 'mfa_check'])->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');
        Route::get('/all', 'indexAll');
        Route::post('/push', 'store');
        Route::put('/read/{notification}', 'read');
        Route::delete('/delete/{id}', 'destroy');
    });
});
