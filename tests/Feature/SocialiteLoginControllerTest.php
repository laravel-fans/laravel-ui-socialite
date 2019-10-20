<?php

namespace sinkcup\LaravelUiSocialite\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use sinkcup\LaravelUiSocialite\SocialAccount;
use sinkcup\LaravelUiSocialite\Socialite\Controllers\SocialiteLoginController;
use sinkcup\LaravelUiSocialite\Tests\TestCase;

class SocialiteLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLogin()
    {
        $response = $this->get('/login');

        $response->assertViewIs('auth.login');
        $response->assertViewHas('social_login', config('auth.social_login'));
        $response->assertViewHas('password_login', config('auth.password_login'));
    }

    public function testHideWeChatWebWhenLoginFromWeChatApp()
    {
        $providers = ['github', 'wechat_service_account'];
        $this->app['config']->set('auth.social_login.providers', array_merge($providers, ['wechat_web']));
        $response = $this->withHeader(
            'user-agent',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)' .
            ' Mobile/15E148 MicroMessenger/7.0.5(0x17000523) NetType/WIFI Language/zh_CN'
        )->get('/login');

        $response->assertViewIs('auth.login');
        $response->assertViewHas('social_login', array_merge(config('auth.social_login'), compact(['providers'])));
        $response->assertViewHas('password_login', config('auth.password_login'));
    }

    public function testHideWeChatServiceAccountWhenLoginFromWeb()
    {
        $providers = ['github', 'wechat_web'];
        $this->app['config']->set('auth.social_login.providers', array_merge($providers, ['wechat_service_account']));
        $response = $this->get('/login');

        $response->assertViewIs('auth.login');
        $response->assertViewHas('social_login', array_merge(config('auth.social_login'), compact(['providers'])));
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

    public function testHandleProviderCallbackForNewUser()
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

    public function testConvertProviderSlugToServiceName()
    {
        $this->assertEquals('wechat_web', SocialiteLoginController::convertProviderSlugToServiceName('wechat-web'));
    }

    public function testHandleProviderCallbackForOldUser()
    {
        $provider = $this->faker->word;
        $this->app['config']->set('services.' . $provider, $this->serviceConfig);
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->token = $this->faker->md5;
        $user = factory(User::class)->create();
        $socialAccount = factory(SocialAccount::class)->create([
            'user_id' => $user->id,
            'provider' => $provider,
        ]);
        $abstractUser
            ->shouldReceive('getId')
            ->andReturn($socialAccount->provider_user_id)
            ->shouldReceive('getName')
            ->andReturn($socialAccount->name)
            ->shouldReceive('getNickname')
            ->andReturn($socialAccount->nickname)
            ->shouldReceive('getEmail')
            ->andReturn($socialAccount->email)
            ->shouldReceive('getAvatar')
            ->andReturn($socialAccount->avatar)
            ->shouldReceive('getRaw')
            ->andReturn($socialAccount->raw);
        Socialite::shouldReceive('driver->scopes->user')->andReturn($abstractUser);

        $response = $this->get('/login/' . $provider . '/callback');
        $response->assertRedirect(route('profile.edit'));
        $this->assertEquals(1, SocialAccount::count());
        $socialAccountDb = SocialAccount::first();
        foreach ($socialAccount->toArray() as $k => $v) {
            if ($k == 'updated_at') {
                continue;
            }
            if ($k == 'access_token') {
                $this->assertNotEquals($v, $socialAccountDb->{$k});
            } else {
                $this->assertEquals($v, $socialAccountDb->{$k});
            }
        }
    }
}
