<?php

use Illuminate\Support\Facades\Route;
use App\Enums\RoleEnum;
use App\Http\Controllers\AssetController;
use Illuminate\Http\Request;

Route::controller(AssetController::class)->group(function () {
    Route::middleware('orgAccess')->group(function () {
        Route::get('/', 'index');
        Route::get('/count', 'count');
        Route::get('/export', 'export');
        Route::get('/details/{id}', 'details');

        Route::middleware('ExampleMiddleware')->get('/middleware', function (Request $request) {
            $middleware = Route::current()->computedMiddleware;

            dd("Middleware List", $middleware);
            return response()->json($middleware);
        });

        Route::post('/test', function (Request $request) {
            return $request->all();
        });


    });




    Route::middleware('ExampleMiddleware')->get('/middleware', function (Request $request) {
        $middleware = Route::current()->computedMiddleware;

        dd("Middleware List", $middleware);
        return response()->json($middleware);
    });

    Route::post('/report', 'report');

    Route::middleware(['role:' . RoleEnum::SuperAdmin . ',' . RoleEnum::Admin . ',' . RoleEnum::OrgSuperAdmin . ',' . RoleEnum::OrgAdmin, 'sq1role'])->group(function () {
        Route::post('/retire', 'retireAsset');
        Route::put('/revoke/{asset}', 'revokeAsset');
    });
});
