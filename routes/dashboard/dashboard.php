<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\DashboardController;

Route::controller(DashboardController::class)->group(function () {
    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::User)->group(function () {
        Route::get('/admin', 'admin');
    });

    Route::middleware('orgAccess')->group(function () {
        // Route::get('/organization-dashboard', 'orgDashboard');
        Route::get('/organization', 'organization');
        Route::get('/risk-distribution', 'riskDistribution');
        Route::get('/age-matrix', 'ageMatrix');
        Route::get('/status-charts', 'statusCharts');
        Route::get('/scanify-score', 'scanifyScore');
    });
});
