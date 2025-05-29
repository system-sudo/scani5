<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\OrganizationController;

Route::controller(OrganizationController::class)->group(function () {
    Route::get('/switch', 'switchOrganization');
    Route::get('/assign', 'showAssign');
    Route::get('/roles', 'roles');
    Route::get('/user-organizations/{user}', 'userOrganizations');
    Route::get('count/user-organizations', 'userOrganizationsCount');

    Route::post('/invite', 'invite');
    Route::post('/reinvite', 'reInvite');

    Route::get('filter-roles', 'filterRoles');

    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::User)->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');
    });

    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin)->group(function () {
        Route::post('/edit-role', 'editRole');
    });

    Route::middleware('role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::OrgSuperAdmin)->group(function () {
        Route::post('/info', 'info');
    });

    Route::middleware('role:' . RoleEnum::SuperAdmin)->group(function () {
        Route::post('/org-status', 'enableDisableOrganization');
        Route::delete('/delete/{organization}', 'destroy');
        Route::get('/edit/{organization}', 'edit');
        Route::put('/update/{organization}', 'update');
        Route::get('/cards', 'orgCards');
    });

    Route::middleware('orgAccess')->group(function () {
        Route::get('/logo', 'logo');
        Route::get('/info/{organization}', 'getInfo');
    });

    Route::withoutMiddleware(['auth:api', 'org_inactive', 'mfa_check'])
        ->middleware('throttle:global')
        ->group(function () {
            Route::get('/verify-link/{token}', 'verifyInvitationLink');
            Route::post('/register', 'register');
        });
});
