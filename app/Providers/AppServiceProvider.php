<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar serviços de regras de negócio
        $this->app->singleton(\App\Services\BusinessRules\PayCommissionService::class);
        $this->app->singleton(\App\Services\BusinessRules\WalletTicketService::class);
        $this->app->singleton(\App\Services\BusinessRules\UpLinesService::class);
        $this->app->singleton(\App\Services\BusinessRules\ExecuteBusinessRule::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
