<?php

namespace sinkcup\LaravelMakeAuthSocialite\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Route;
use sinkcup\LaravelMakeAuthSocialite\PackageServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use WithFaker;
    protected $serviceConfig;

    protected function getPackageProviders($app)
    {
        return [PackageServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Route::get('login', [
            'uses' => 'App\Http\Controllers\Auth\LoginController@showLoginForm',
            'as' => 'login',
        ]);
        Route::get('login/{provider}', [
            'uses' => 'App\Http\Controllers\Auth\LoginController@redirectToProvider',
        ]);
        Route::get('login/{provider}/callback', [
            'uses' => 'App\Http\Controllers\Auth\LoginController@handleProviderCallback',
        ]);
        Route::get('settings/profile', [
            'uses' => 'App\Http\Controllers\ProfileController@edit',
            'as' => 'profile.edit',
        ]);
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/app/Http/Controllers/', 0755, true);
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/routes/');
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/app/Http/Controllers/Auth/');
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/tests/Feature/', 0755, true);
        @unlink(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/routes/web.php');
        touch(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/routes/web.php');
        copy(__DIR__ . '/User.stub', __DIR__ . '/../vendor/orchestra/testbench-core/laravel/app/User.php');
        $this->artisan('make:auth', ['--force' => true])->run();
        $this->artisan('make:auth-socialite', ['--force' => true])->run();
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
        $this->artisan('migrate')->run();
        $this->app->register(\Laravel\Socialite\SocialiteServiceProvider::class);
        $this->app->make('Illuminate\Contracts\Http\Kernel')
            ->pushMiddleware('Illuminate\Session\Middleware\StartSession');
        $client_id = $this->faker->md5;
        $client_secret = $this->faker->sha1;
        $redirect = $this->faker->url;
        $this->serviceConfig = compact('client_id', 'client_secret', 'redirect');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
