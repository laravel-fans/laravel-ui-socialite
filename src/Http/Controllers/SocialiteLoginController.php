<?php

namespace sinkcup\LaravelMakeAuthSocialite\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
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
        return view('auth.login', ['social_login_providers' => config('auth.social_login.providers')]);
    }

    /**
     * Redirect the user to the Socialite Provider authentication page.
     *
     * @param $provider string
     * @return Response
     */
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from Socialite Provider.
     *
     * @param Request $request
     * @param string $provider
     * @return Response
     */
    public function handleProviderCallback($provider)
    {
        try {
            $remote_user = Socialite::driver($provider)->user();
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

        $social_account = SocialAccount::firstOrNew([
            'provider' => $provider,
            'provider_user_id' => $remote_user->getId(),
        ]);
        if (!empty($social_account->user)) {
            $user = $social_account->user;
        } else {
            $app_user = config('auth.providers.users.model');
            $name = $remote_user->getName() ?: $remote_user->getNickname();
            $user = $app_user::firstOrCreate([
                'email' => $provider. '.' . $remote_user->getId() . '@example.com', // faker for email unique in db
                'name' => $name ?: $provider . ' user',
            ]);
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
}
