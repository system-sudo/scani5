<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::none() // No limit for authenticated users (protected routes)
                : Limit::perMinute(60)->by($request->ip()); // Limit for unauthenticated (non-protected)
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60); // 60 requests per minute limit is default
        });


        $this->registerPolicies();

        $expiry_day = 1;

        Passport::tokensCan([
            '2fa_status_registered' => 'User is in registered state',
            '2fa_status_verified' => 'User has completed 2FA verification'
        ]);
        Passport::tokensExpireIn(now()->addDays($expiry_day));
        Passport::refreshTokensExpireIn(now()->addDays($expiry_day));
        Passport::personalAccessTokensExpireIn(now()->addDays($expiry_day));
    }
}
