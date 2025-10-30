<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Lead capture rate limiting
        RateLimiter::for('lead-capture', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Lead status checking rate limiting
        RateLimiter::for('lead-status', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Lead unsubscribe rate limiting
        RateLimiter::for('lead-unsubscribe', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });

        // General API rate limiting
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}