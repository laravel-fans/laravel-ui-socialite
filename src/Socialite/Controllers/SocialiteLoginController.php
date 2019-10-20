<?php

namespace sinkcup\LaravelUiSocialite\Socialite\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ViewErrorBag;
use Laravel\Socialite\Facades\Socialite;
use sinkcup\LaravelUiSocialite\SocialAccount;

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
    public function redirectToProvider($providerSlug)
    {
        $provider = self::convertProviderSlugToServiceName($providerSlug);
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleProviderCallback($providerSlug)
    {
        $provider = self::convertProviderSlugToServiceName($providerSlug);
        try {
            $remote_user = Socialite::driver($provider)
                ->scopes(config("services.{$provider}.scopes"))
                ->user();
        } catch (\Exception $e) {
            Log::warning('Socialite Login failed', [
                'provider' => $provider,
                'exception' => [
                    'name' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
            ]);
            return $this->sendFailedSocialLoginResponse($provider);
        }

        // if logged in, should link multiple auth providers to an account
        $user_id = auth()->user()->id ?? null;
        // if you have defined "union_id_with" in config, it will be load at here
        // some providers use one union id, e.g., WeChat Web, WeChat Service Account
        if (!empty($union_id_with_providers = config("services.{$provider}.union_id_with"))) {
            $user_id = SocialAccount::whereIn('provider', array_diff($union_id_with_providers, [$provider]))
                ->where('provider_user_id', $remote_user->getId())
                ->whereNotNull('user_id')
                ->value('user_id');
        }
        $social_account = SocialAccount::firstOrNew([
            'provider' => $provider,
            'provider_user_id' => $remote_user->getId(),
        ], ['user_id' => $user_id]);
        $name = $remote_user->getName() ?: $remote_user->getNickname();
        if (!empty($social_account->user)) {
            $user = $social_account->user;
        } else {
            $user_model = config('auth.providers.users.model');
            // faker email for unique in db
            $email = $remote_user->getEmail() ?: $provider . '.' . $remote_user->getId() . '@example.com';
            $user = $user_model::where('email', $email)->first();
            if (empty($user)) {
                $user = $user_model::create([
                    'email' => $email,
                    'name' => $name ?: $provider . ' user',
                ]);
            }
            $social_account->user()->associate($user);
        }
        $social_account->sync($remote_user);
        if (!empty($remote_user->getAvatar())) {
            $user->avatar = $remote_user->getAvatar();
        }
        if (!empty($name)) {
            $user->name = $name;
        }
        $user->save();
        auth()->login($user);
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the failed social login response instance.
     *
     * @param $provider
     * @return \Symfony\Component\HttpFoundation\Response
     *
     */
    protected function sendFailedSocialLoginResponse($provider)
    {
        return redirect()->route('login')->withErrors([
            $provider => [trans('auth.failed')],
        ]);
    }

    /**
     * Convert provider slug to service name which is using in config/services.php
     * @param string $providerSlug e.g., paypal-sandbox
     * @return string e.g., paypay_sandbox
     */
    public static function convertProviderSlugToServiceName($providerSlug)
    {
        return str_replace('-', '_', $providerSlug);
    }
}
