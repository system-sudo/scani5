<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->middleware('login_attempt');
    Route::post('/forgot-password', 'forgotPassword');
    Route::get('/forgot-password-verifycheck/{token}', 'verifyForgotPasswordLink');
    Route::post('/update-password', 'resetPassword');

    Route::post('/logout', 'logout')->middleware('auth:api');
});
