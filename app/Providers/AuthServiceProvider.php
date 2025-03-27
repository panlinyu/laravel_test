<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        date_default_timezone_set('Asia/Shanghai');
        // 设置访问令牌过期时间
        Passport::tokensExpireIn(Carbon::now()->addHours(48));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(5));
    }
}
