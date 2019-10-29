# Laravel UI Socialite

[![CircleCI](https://circleci.com/gh/sinkcup/laravel-ui-socialite.svg?style=svg)](https://circleci.com/gh/sinkcup/laravel-ui-socialite)
[![codecov](https://codecov.io/gh/sinkcup/laravel-ui-socialite/branch/master/graph/badge.svg)](https://codecov.io/gh/sinkcup/laravel-ui-socialite)
[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/sinkcup/laravel-ui-socialite/blob/master/LICENSE)

Automatically generate database, pages, and routes for Laravel Socialite. Just like `php artisan ui:auth`.

Login with multiple providers using the same email will be determined as one user.

When logged in, you can link all providers to the current user, and login with them next time.

Special handling for [WeChat](https://sinkcup.github.io/laravel-socialite-wechat-login).

supporting Laravel 6.0!

## install

```
composer require sinkcup/laravel-ui-socialite
php artisan ui vue
php artisan ui:auth
php artisan ui:socialite
php artisan migrate
```

## config

add to `config/services.php`:

```
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
        'scopes' => env('GITHUB_SCOPES'), // optional
    ],
```

add to `.env`:

```
AUTH_SOCIAL_LOGIN_PROVIDERS=Facebook,Twitter,Linkedin,Google,GitHub,GitLab,Bitbucket,wechat_web,wechat_service_account
GITHUB_CLIENT_ID=foo
GITHUB_CLIENT_SECRET=bar
GITHUB_CALLBACK_URL=http://localhost/login/github/callback
GITHUB_SCOPES=user:email,public_repo

# disable password login
AUTH_PASSWORD_LOGIN_ENABLED=0

# disable register
AUTH_OPTIONS_REGISTER=0
```

## screenshots

![Laravel Socialite Login page](https://user-images.githubusercontent.com/4971414/64499841-477d8000-d2ed-11e9-8981-e6764378462e.png)
![GitHub OAuth Login](https://user-images.githubusercontent.com/4971414/64499857-5a905000-d2ed-11e9-8b75-3686aab2abf1.png)
![Laravel Socialite Profile page and Linked Accounts](https://user-images.githubusercontent.com/4971414/64499866-63812180-d2ed-11e9-82c3-68f5320026c8.png)
![Laravel Socialite link multiple providers to one user](https://user-images.githubusercontent.com/4971414/64498074-45172800-d2e5-11e9-824c-9189d46de259.png)
