<?php

namespace sinkcup\LaravelUiSocialite\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use sinkcup\LaravelUiSocialite\SocialAccount;
use sinkcup\LaravelUiSocialite\Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testEdit()
    {
        $user = factory(User::class)->create();
        $social_account = factory(SocialAccount::class)->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get('/settings/profile');

        $response->assertViewIs('settings.profile');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('social_login_providers', config('auth.social_login.providers'));
        $response->assertViewHas('linked_providers', [$social_account->provider]);
    }

    public function testEditShouldHideWeChatWebWhenVisitFromWeChatApp()
    {
        $providers = ['github', 'wechat_service_account'];
        $this->app['config']->set('auth.social_login.providers', array_merge($providers, ['wechat_web']));
        $user = factory(User::class)->create();
        $social_account = factory(SocialAccount::class)->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)
            ->withHeader(
                'user-agent',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 12_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko)' .
                ' Mobile/15E148 MicroMessenger/7.0.5(0x17000523) NetType/WIFI Language/zh_CN'
            )->get('/settings/profile');

        $response->assertViewIs('settings.profile');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('social_login_providers', $providers);
        $response->assertViewHas('linked_providers', [$social_account->provider]);
    }

    public function testEditShouldHideWeChatServiceAccountWhenVisitFromWeb()
    {
        $providers = ['github', 'wechat_web'];
        $this->app['config']->set('auth.social_login.providers', array_merge($providers, ['wechat_service_account']));
        $user = factory(User::class)->create();
        $response = $this->actingAs($user)->get('/settings/profile');

        $response->assertViewIs('settings.profile');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('social_login_providers', $providers);
        $response->assertViewHas('linked_providers', []);
    }

    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $data = [
            'email' => $this->faker->safeEmail,
            'name' => $this->faker->name,
        ];
        $response = $this->actingAs($user)->put('/settings/profile', $data);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertEquals($data['email'], $user->email);
        $this->assertEquals($data['name'], $user->name);
    }
}
