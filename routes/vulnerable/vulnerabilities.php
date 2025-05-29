<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VulnerabilityController;

Route::controller(VulnerabilityController::class)->group(function () {
    Route::middleware('orgAccess')->group(function () {
        Route::get('/', 'index');
        Route::get('/details/{vulnerability}', 'details');
        Route::get('/assets/{vulnerability}', 'assets');

        Route::prefix('/count')->group(function () {
            Route::get('/', 'count');
            Route::get('/asset-vulnerabilities/{asset}', 'assetVulnerabilitiesCount');
            Route::get('/total-exploits/{vulnerability?}', 'countExploits');
            Route::get('/patch/{vulnerability}', 'countPatches');
        });

        Route::get('/export', 'export');
        Route::get('/asset-vulnerabilities/{asset}', 'assetVulnerabilities');
        Route::get('/patch/{vulnerability}', 'patches');
        Route::get('/patch-cve/{vulnerability}', 'cve');
        Route::get('/exploit/{asset}', 'exploits');
        Route::get('/vulnerability-exploits/{vulnerability}', 'vulnerabilityExploits');
        Route::get('/exploits-vulnerability/{exploit}', 'exploitsVulnerability');
        Route::get('/total-exploits', 'totalExploits');
    });
});
