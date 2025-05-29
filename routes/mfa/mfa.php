<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\MFAController;

Route::controller(MFAController::class)->group(function () {
    Route::middleware(['secure_header', 'auth:api', 'org_inactive'])->group(function () {
        Route::get('/qrcode', 'showQrCode');
        Route::post('/verify/qr-code', 'verifyqr');
        Route::post('/verify/totp', 'verifyTotp');
        Route::post('/request-regenerate/totp', 'requestRegenerateTotp');

        Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin)->group(function () {
            Route::post('/regenerate/totp', 'RegenerateTotp');
        });
    });
});
