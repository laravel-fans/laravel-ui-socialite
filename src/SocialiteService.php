<?php

namespace LaravelFans\UiSocialite;

use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new instance.
     *
     * @parvi vam  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function createUser(string $providerSlug, bool $stateless = false)
    {
        $provider = SocialiteService::convertProviderSlugToServiceName($providerSlug);
        try {
            $driver = Socialite::driver($provider);
            $driver = $stateless ? $driver->stateless() : $driver;
            $remote_user = $driver->scopes(config("services.{$provider}.scopes"))
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
            throw $e;
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
        return $user;
    }

    /**
     * Convert provider slug to service name which is using in config/services.php
     * @param string $providerSlug e.g., paypal-sandbox
     * @return string e.g., paypay_sandbox
     */
    public static function convertProviderSlugToServiceName(string $providerSlug)
    {
        return str_replace('-', '_', $providerSlug);
    }
}
