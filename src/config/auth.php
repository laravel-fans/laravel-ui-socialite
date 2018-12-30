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
        'providers' => preg_split('/,/', env('AUTH_SOCIAL_LOGIN_PROVIDERS'), null, PREG_SPLIT_NO_EMPTY), // can not use explode, see vlucas/phpdotenv#175
    ],
];
