<?php

namespace LaravelFans\UiSocialite;

use Illuminate\Support\ServiceProvider;

class UiSocialiteServiceProvider extends ServiceProvider
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

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'ui-socialite-migrations');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auth.php', 'auth');
        $this->app->singleton(SocialiteService::class, function ($app) {
            return new SocialiteService($app);
        });
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
