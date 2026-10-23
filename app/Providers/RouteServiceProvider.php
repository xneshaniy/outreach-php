<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            $this->mapApiRoutes();
            $this->mapWebRoutes();
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('email-send', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }

    protected function mapWebRoutes(): void
    {
        $this->app->routes()->group([
            'middleware' => 'web',
            'namespace' => 'App\Http\Controllers',
        ], base_path('routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        $this->app->routes()->group([
            'middleware' => 'api',
            'prefix' => 'api',
            'namespace' => 'App\Http\Controllers\Api',
        ], base_path('routes/api.php'));
    }
}