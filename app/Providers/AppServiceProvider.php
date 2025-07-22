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
        // Fix for Laravel 10 Migrator bug - bind correct Filesystem instance
        $this->app->when('Illuminate\Database\Migrations\Migrator')
            ->needs('Illuminate\Filesystem\Filesystem')
            ->give(function ($app) {
                return new \Illuminate\Filesystem\Filesystem();
            });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
