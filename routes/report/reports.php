<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\ReportController;

Route::controller(ReportController::class)->group(function () {
    Route::middleware('orgAccess')->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');
        Route::get('/ip', 'showIp');
    });

    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::OrgSuperAdmin . ',' . RoleEnum::OrgAdmin)
        ->group(function () {
            Route::post('/generate', 'store')->middleware('sq1role');
            Route::delete('/delete/{organization}/{report}', 'destroy')->middleware('orgAccess');
        });

    Route::post('/download', 'download');
});
