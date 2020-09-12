<?php

namespace LaravelFans\UiSocialite\Socialite\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

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
        return $providers;
    }
}
