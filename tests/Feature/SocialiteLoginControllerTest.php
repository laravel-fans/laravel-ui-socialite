<?php

namespace sinkcup\LaravelMakeAuthSocialite\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use sinkcup\LaravelMakeAuthSocialite\Tests\TestCase;

class SocialiteLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLogin()
    {
        $response = $this->withSession(['foo' => 'bar'])->get('/login');

        $response->assertViewIs('auth.login');
        $response->assertViewHas('social_login', config('auth.social_login'));
        $response->assertViewHas('password_login', config('auth.password_login'));
    }
}
