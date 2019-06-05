<?php

namespace sinkcup\LaravelMakeAuthSocialite\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use sinkcup\LaravelMakeAuthSocialite\SocialAccount;

class SocialiteLoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Social Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller redirects visitors to socialite providers login page and
    | auto creating account to the application.
    |
    */

    use AuthenticatesUsers;

     /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        $social_login_providers = config('auth.social_login.providers');
        // "WeChat Service Account Login" must be used in WeChat app.
        if (!stripos(request()->header('user-agent'), 'MicroMessenger')) {
            if (in_array('wechat_service_account', $social_login_providers)) {
                unset($social_login_providers[array_search('wechat_service_account', $social_login_providers)]);
            }
        } elseif (in_array('wechat_service_account', $social_login_providers)
            && in_array('wechat_web', $social_login_providers)) {
            unset($social_login_providers[array_search('wechat_web', $social_login_providers)]);
        }
        // "WeChat Web Login" will failed if you:
        // open URL in WeChat app and then "Scan QR Code in Image", or "Choose QR Code from Album"
        if (in_array('wechat_web', $social_login_providers)) {
            // set state for QR iframe Login
            session()->put('state', csrf_token());
        }
        return view('auth.login', ['social_login_providers' => $social_login_providers]);
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
        Log::debug(__METHOD__, ['scopes' => config("services.{$provider}.scopes"), 'key' => "services.{$provider}.scopes"]);
        return Socialite::driver($provider)
            // if you have defined "scopes" in config, it will be load at here
            // docs: https://laravel.com/docs/5.8/socialite#access-scopes
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
        Log::debug(__METHOD__, ['remote_user' => $remote_user]);

        // if you have defined "union_id_with" in config, it will be load at here
        // some providers use one union id, e.g., WeChat Web, WeChat Service Account
        $user_id = null;
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
            $email = $remote_user->getEmail() ?: $provider. '.' . $remote_user->getId() . '@example.com'; // faker for email unique in db
            $user = $user_model::where('email', $email)->first();
            if (empty($user)) {
                $user = $user_model::create([
                    'email' => $email,
                    'name' => $name ?: $provider . ' user',
                ]);
            }
            $social_account->user()->associate($user);
        }
        $social_account->nickname = $remote_user->getNickname();
        $social_account->name = $remote_user->getName();
        $social_account->email = $remote_user->getEmail();
        $social_account->avatar = $remote_user->getAvatar();
        $social_account->raw = $remote_user->getRaw();
        $social_account->access_token = $remote_user->token;
        $social_account->refresh_token = $remote_user->refreshToken; // not always provided
        $social_account->expires_in = $remote_user->expiresIn;
        $social_account->save();
        Log::debug(__METHOD__, ['social_account' => $social_account->toArray()]);
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
        Log::debug(__METHOD__, ['providerSlug' => $providerSlug]);
        return str_replace('-', '_', $providerSlug);
    }
}
