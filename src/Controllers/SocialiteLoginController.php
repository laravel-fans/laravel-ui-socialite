<?php

namespace LaravelFans\UiSocialite\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\ViewErrorBag;
use Laravel\Socialite\Facades\Socialite;
use LaravelFans\UiSocialite\SocialiteService;

class SocialiteLoginController extends Controller
{
    use AuthenticatesUsers;

    /*
    |--------------------------------------------------------------------------
    | Social Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller redirects visitors to socialite providers login page and
    | auto creating account to the application.
    |
    */

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        $providers = self::formatProviders(config('auth.social_login.providers'), request());
        // "WeChat Web Login" will failed if you:
        // open URL in WeChat app and then "Scan QR Code in Image", or "Choose QR Code from Album"
        if (in_array('wechat_web', $providers)) {
            // set state for QR iframe Login
            session()->put('state', csrf_token());
        }
        return view('auth.login', [
            'social_login' => array_merge(config('auth.social_login'), compact('providers')),
            'password_login' => config('auth.password_login'),
            'errors' => session('errors', new ViewErrorBag()), // HACK: only for test
        ]);
    }

    /**
     * Redirect the user to the Socialite Provider authentication page.
     *
     * @param string $providerSlug provider slug, e.g., paypal-sandbox, wechat-web
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectToProvider(string $providerSlug)
    {
        $provider = SocialiteService::convertProviderSlugToServiceName($providerSlug);
        return Socialite::driver($provider)
            // if you have defined "scopes" in config, it will be load at here
            // docs: https://laravel.com/docs/socialite#access-scopes
            ->scopes(config("services.{$provider}.scopes"))
            ->redirect();
    }

    /**
     * Obtain the user information from Socialite Provider.
     *
     * @param string $providerSlug
     * @param SocialiteService $socialiteService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleProviderCallback(string $providerSlug, SocialiteService $socialiteService)
    {
        try {
            $user = $socialiteService->createUser($providerSlug);
        } catch (\Exception $e) {
            return $this->sendFailedSocialLoginResponse($providerSlug);
        }

        auth()->login($user);
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the failed social login response instance.
     *
     * @param string $providerSlug
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    protected function sendFailedSocialLoginResponse(string $providerSlug)
    {
        $provider = SocialiteService::convertProviderSlugToServiceName($providerSlug);
        return redirect()->route('login')->withErrors([
            $provider => [trans('auth.failed')],
        ]);
    }

}
