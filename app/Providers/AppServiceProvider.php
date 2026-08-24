<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind StockService as singleton
        $this->app->singleton(\App\Services\StockService::class);
    }

    public function boot(): void
    {
        // Use Bootstrap pagination
        Paginator::useBootstrapFive();
    }
}
