<?php

namespace sinkcup\LaravelMakeAuthSocialite\Http\Controllers;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
     * @throws ValidationException
     */
    public function handleProviderCallback(Request $request, $provider)
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
            return $this->sendFailedSocialLoginResponse($request, $provider);
        }

        $social_account = SocialAccount::firstOrNew([
            'provider' => $provider,
            'provider_user_id' => $remote_user->getId(),
        ]);
        if (!empty($social_account->user)) {
            $user = $social_account->user;
        } else {
            $app_user = config('auth.providers.users.model');
            $user = $app_user::firstOrCreate([
                'email' => $provider. '.' . $remote_user->getId() . '@example.com', // faker for email unique in db
                'name' => $remote_user->getName(),
            ]);
            $social_account->user()->associate($user);
        }
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
     * @param  \Illuminate\Http\Request  $request
     * @param $provider
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedSocialLoginResponse(Request $request, $provider)
    {
        throw ValidationException::withMessages([
            $provider => [trans('auth.failed')],
        ]);
    }
}
