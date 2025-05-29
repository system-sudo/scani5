<?php

use App\Http\Middleware\ExampleMiddleware;
use App\Http\Middleware\MfacheckMiddleware;
use App\Http\Middleware\OrgAccess;
use App\Http\Middleware\OrgInactive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecureHeadersMiddleware;
use App\Http\Middleware\Sq1Middleware;
use App\Http\Middleware\TrackLoginAttempts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Route::middleware('api')
            Route::middleware('api')
            ->prefix('api')
            ->group(function () {
                Route::middleware(['secure_header', 'auth:api', 'org_inactive', 'mfa_check'])
                ->group(function () {

                    // assets routes
                    Route::prefix('assets')
                    ->group(base_path('routes/asset/assets.php'));

                    //tags routes
                    Route::prefix('tags')
                    ->group(base_path('routes/tag/tags.php'));

                    // dashboard routes
                    Route::prefix('dashboard')
                    ->group(base_path('routes/dashboard/dashboard.php'));

                    // organizations routes
                    Route::prefix('organizations')
                    ->group(base_path('routes/organization/organizations.php'));

                    // reports routes
                    Route::prefix('reports')
                    ->group(base_path('routes/report/reports.php'));

                    // Vulnerability routes
                    Route::prefix('vulnerabilities')
                    ->group(base_path('routes/vulnerable/vulnerabilities.php'));

                    // users routes
                    Route::prefix('users')
                    ->group(base_path('routes/user/users.php'));

                });

                Route::middleware(['secure_header', 'throttle:global'])
                        ->group(function () {
                            // notifications routes
                            Route::prefix('notifications')
                                ->group(base_path('routes/notification/notifications.php'));

                            // auth routes
                            Route::prefix('auth')
                                ->group(base_path('routes/auth/auth.php'));

                            // aborts routes
                            Route::prefix('aborts')
                                ->group(base_path('routes/abort/aborts.php'));
                        });

                // mfa routes
                Route::prefix('mfa')
                ->group(base_path('routes/mfa/mfa.php'));

                // logs routes
                Route::prefix('logs')
                ->group(base_path('routes/log/logs.php'));

            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'orgAccess' => OrgAccess::class,
            'role' => RoleMiddleware::class,
            'sq1role' => Sq1Middleware::class,
            'login_attempt' => TrackLoginAttempts::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'org_inactive' => OrgInactive::class,
            'secure_header' => SecureHeadersMiddleware::class,
            'mfa_check' => MfacheckMiddleware::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
