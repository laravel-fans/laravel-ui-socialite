<?php

namespace sinkcup\LaravelUiSocialite;

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
                SocialiteCommand::class,
            ]);
        }
        // HACK: package migrations be migrated before laravel migrations when run test
        // error: There is no column with name 'password' on table 'users'.
        if ($this->app->config['app']['name'] != 'testbench') {
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
        $this->mergeConfigFrom(__DIR__ . '/config/auth.php', 'auth');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            SocialiteCommand::class,
        ];
    }
}
