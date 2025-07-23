<?php

namespace LaravelFans\UiSocialite\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use LaravelFans\UiSocialite\SocialiteService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialiteLoginController extends BaseController
{
    use AuthorizesRequests;

    /*
    |--------------------------------------------------------------------------
    | Social Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller redirects visitors to socialite providers login page and
    | auto creating account to the application.
    |
    */

    public static function formatProviders($providers, Request $request)
    {
        // "WeChat Service Account Login" must be used in WeChat app.
        if (!stripos($request->header('user-agent'), 'MicroMessenger')) {
            if (in_array('wechat_service_account', $providers)) {
                unset($providers[array_search('wechat_service_account', $providers)]);
            }
        } elseif (
            in_array('wechat_service_account', $providers)
            && in_array('wechat_web', $providers)
        ) {
            unset($providers[array_search('wechat_web', $providers)]);
        }
        $result = [];
        foreach ($providers as $provider) {
            $slug = strtolower(str_replace('_', '-', $provider));
            $result[$slug] = $provider;
        }
        return $result;
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
            Log::error($e->getMessage());
            return $this->sendFailedSocialLoginResponse($providerSlug);
        }

        auth()->login($user);
        return redirect()->intended('dashboard');
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
