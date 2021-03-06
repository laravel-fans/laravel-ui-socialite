<?php

namespace LaravelFans\UiSocialite\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Route;
use Laravel\Ui\UiServiceProvider;
use LaravelFans\UiSocialite\UiSocialiteServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use WithFaker;

    protected $serviceConfig;

    protected function getPackageProviders($app)
    {
        return [
            UiSocialiteServiceProvider::class,
            UiServiceProvider::class,
        ];
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
            'uses' => 'App\Http\Controllers\Settings\ProfileController@edit',
            'as' => 'profile.edit',
        ]);
        Route::put('settings/profile', [
            'uses' => 'App\Http\Controllers\Settings\ProfileController@update',
            'as' => 'profile.update',
        ]);
        Route::post('logout', 'Auth\LoginController@logout')->name('logout');
        $laravel_path = __DIR__ . '/../vendor/orchestra/testbench-core/laravel';
        @mkdir($laravel_path . '/app/Http/Controllers/', 0755, true);
        @mkdir($laravel_path . '/routes/');
        @mkdir($laravel_path . '/app/Http/Controllers/Auth/');
        @mkdir($laravel_path . '/tests/Feature/', 0755, true);
        @unlink($laravel_path . '/routes/web.php');
        touch($laravel_path . '/routes/web.php');
        @mkdir($laravel_path . '/database/factories/LaravelFans/UiSocialite/Models/', 0755, true);
        copy(__DIR__ . '/UserFactory.stub', $laravel_path . '/database/factories/UserFactory.php');
        copy(__DIR__ . '/SocialAccountFactory.stub', $laravel_path . '/database/factories/LaravelFans/UiSocialite/Models/SocialAccountFactory.php');
        @mkdir($laravel_path . '/app/Models/');
        copy(__DIR__ . '/User.stub', $laravel_path . '/app/Models/User.php');
        $this->artisan('ui:auth', ['--force' => true])->run();
        $this->artisan('ui:socialite', ['--force' => true])->run();
        $this->artisan('vendor:publish', ['--provider' => "LaravelFans\UiSocialite\UiSocialiteServiceProvider"])->run();

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
        $app['config']->set('app.name', 'testbench');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', 'App\\Models\\User');
    }
}
