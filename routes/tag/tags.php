<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\TagsController;

Route::controller(TagsController::class)->group(function () {
    Route::middleware('orgAccess')->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');

        Route::middleware(['role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::OrgSuperAdmin . ',' . RoleEnum::OrgAdmin, 'sq1role'])->group(function () {
            Route::delete('/all', 'destroyAll');
            Route::get('/report-tags', 'reportTags');
            Route::delete('/{organization}/{tag}', 'destroy');
        });
    });
    Route::middleware(['role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::OrgSuperAdmin . ',' . RoleEnum::OrgAdmin, 'sq1role'])
        ->group(function () {
            // Route::post('/delete-asset-tag', 'deleteAssetTag');
            Route::put('/update/{tag}', 'update');
            Route::post('/', 'store');
            Route::post('/assign', 'assign');
            Route::delete('/remove/{organizaion}/{module}/{type}', 'remove')->middleware('orgAccess');
        });
});
