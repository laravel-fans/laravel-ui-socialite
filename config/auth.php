<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social Login Providers
    |--------------------------------------------------------------------------
    |
    | This defines social providers on login page.
    |
    */

    'social_login' => [
        // HACK: can not use explode, see vlucas/phpdotenv#175
        'providers' => preg_split('/,/', env('AUTH_SOCIAL_LOGIN_PROVIDERS'), null, PREG_SPLIT_NO_EMPTY),
        'enabled' => env('AUTH_SOCIAL_LOGIN_ENABLED', true),
    ],

    'password_login' => [
        'enabled' => env('AUTH_PASSWORD_LOGIN_ENABLED', true),
    ],

    'options' => [
        'register' => env('AUTH_OPTIONS_REGISTER', true),
        'reset' => env('AUTH_OPTIONS_RESET', true),
        'verify' => env('AUTH_OPTIONS_VERIFY', false),
    ],
];
