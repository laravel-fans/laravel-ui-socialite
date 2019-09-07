<?php

namespace sinkcup\LaravelMakeAuthSocialite\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use sinkcup\LaravelMakeAuthSocialite\SocialAccount;
use sinkcup\LaravelMakeAuthSocialite\Tests\TestCase;

class SocialiteLoginControllerTest extends TestCase
{
    //use RefreshDatabase;

    public function testLogin()
    {
        $response = $this->withSession(['foo' => 'bar'])->get('/login');

        $response->assertViewIs('auth.login');
        $response->assertViewHas('social_login', config('auth.social_login'));
        $response->assertViewHas('password_login', config('auth.password_login'));
    }

    public function testRedirectToProvider()
    {
        $provider = 'github';
        $this->app['config']->set('services.' . $provider, $this->serviceConfig);

        $response = $this->get('/login/' . $provider);
        $authUrl = 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => $this->serviceConfig['client_id'],
            'redirect_uri' => $this->serviceConfig['redirect'],
            'scope' => 'user:email',
            'response_type' => 'code',
            'state' => session('state'),
        ]);

        $response->assertRedirect($authUrl);
    }

    public function testHandleProviderCallback()
    {
        $provider = 'github';
        $this->app['config']->set('services.' . $provider, $this->serviceConfig);
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->token = $this->faker->md5;
        $socialAccount = [
            'provider_user_id' => $this->faker->randomNumber(),
            'name' => $this->faker->userName,
            'nickname' => $this->faker->name,
            'email' => $this->faker->email,
            'avatar' => $this->faker->url,
            'raw' => [],
        ];
        $abstractUser
            ->shouldReceive('getId')
            ->andReturn($socialAccount['provider_user_id'])
            ->shouldReceive('getName')
            ->andReturn($socialAccount['name'])
            ->shouldReceive('getNickname')
            ->andReturn($socialAccount['nickname'])
            ->shouldReceive('getEmail')
            ->andReturn($socialAccount['email'])
            ->shouldReceive('getAvatar')
            ->andReturn($socialAccount['avatar'])
            ->shouldReceive('getRaw')
            ->andReturn($socialAccount['raw']);
        Socialite::shouldReceive('driver->scopes->user')->andReturn($abstractUser);

        $response = $this->get('/login/' . $provider . '/callback');
        $response->assertRedirect(route('profile.edit'));
        $this->assertEquals(1, SocialAccount::count());
        $socialAccountDb = SocialAccount::first();
        foreach ($socialAccount as $k => $v) {
            $this->assertEquals($v, $socialAccountDb->{$k});
        }
    }
}
