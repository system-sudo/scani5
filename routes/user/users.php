<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\UserController;

Route::controller(UserController::class)->group(function () {
    // Users API
    Route::get('/', 'index')->middleware('orgAccess');

    Route::prefix('/count')->group(function () {
        Route::get('/', 'count');
        Route::get('/organization-users', 'orguserCount')->middleware('orgAccess');
    });

    // Profile Setting API
    Route::get('/{user}', 'show');
    Route::put('/{user}', 'update');
    Route::post('/change-password', 'changePassword');

    Route::get('/organization-users/{org}', 'organizationUsers')->middleware('orgAccess');

    Route::middleware(['role:' . RoleEnum::SuperAdmin, 'sq1role', 'orgAccess'])->group(function () {
        Route::post('/unlock', 'unlock');
    });

    Route::middleware(['role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin, 'sq1role', 'orgAccess'])
        ->group(function () {
            Route::delete('/organizations-user/{org}/{user}', 'deleteOrganizationUser');
        });

    // User delete
    Route::middleware(['role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::OrgSuperAdmin . ',' . RoleEnum::OrgAdmin, 'sq1role'])
        ->group(function () {
            Route::delete('{org}/{user}', 'destroy');
        });

    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin)->group(function () {
        Route::middleware('sq1role')->group(function () {
            Route::post('/assign-organizations', 'assignOrganization');
            Route::delete('/user-organization/{org}/{user}', 'removeUserOrganization');
        });
    });
});
