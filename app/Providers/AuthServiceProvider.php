<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
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

    public function register()
    {
        Passport::ignoreRoutes();
    }
}
