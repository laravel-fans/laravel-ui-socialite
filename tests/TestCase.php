<?php

namespace sinkcup\LaravelMakeAuthSocialite\Tests;

use Illuminate\Support\Facades\Route;
use sinkcup\LaravelMakeAuthSocialite\PackageServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [PackageServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Route::get('login', ['as' => 'login', 'uses' => 'sinkcup\LaravelMakeAuthSocialite\Http\Controllers\SocialiteLoginController@showLoginForm']);
        $this->loadLaravelMigrations();
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/app/Http/Controllers/', 0755, true);
        @mkdir(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/routes/');
        touch(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/routes/web.php');
        $this->artisan('make:auth', ['--force' => true])->run();
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
        $this->artisan('migrate')->run();
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