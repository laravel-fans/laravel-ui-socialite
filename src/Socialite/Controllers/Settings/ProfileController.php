<?php

namespace LaravelFans\UiSocialite\Socialite\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use LaravelFans\UiSocialite\SocialAccount;
use LaravelFans\UiSocialite\Socialite\Controllers\Controller;
use LaravelFans\UiSocialite\Socialite\Controllers\SocialiteLoginController;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $user = auth()->user();
        $social_login_providers = self::formatProviders(config('auth.social_login.providers'), request());
        $linked_providers = SocialAccount::where('user_id', $user->id)->select(['provider'])->pluck('provider')->all();
        return view('settings.profile', compact('user', 'social_login_providers', 'linked_providers') +
            ['errors' => session('errors', new ViewErrorBag())]); // HACK: only for test
    }

    /**
     * Update the user's profile.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = $request->user();
        Validator::make($request->all(), [
            'email' => [
                Rule::unique('users')->ignore($user->id),
            ],
            'name' => 'string|max:255',
        ])->validate();
        $user->update($request->all());
        return redirect(route('profile.edit'));
    }
}
