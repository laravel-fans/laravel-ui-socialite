<?php

namespace sinkcup\LaravelMakeAuthSocialite;

use Illuminate\Support\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MakeAuthSocialite::class,
            ]);
        }
        // HACK: package migrations be migrated before laravel migrations when run test
        // error: There is no column with name 'password' on table 'users'.
        if (!$this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/auth.php', 'auth'
        );
    }
}
