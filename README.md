# Laravel Make Auth Socialite

[![LICENSE](https://img.shields.io/badge/license-Anti%20996-blue.svg)](https://github.com/sinkcup/laravel-make-auth-socialite/blob/master/LICENSE)

Automatically generate database, pages, and routes for Laravel Socialite. Just like `php artisan make:auth`.

Login with multiple providers using the same email will be determined as one user.

When logged in, you can link all providers to the current user, and login with them next time.

Special handling for [WeChat](https://sinkcup.github.io/laravel-socialite-wechat-login).

supporting Laravel 5.5 and 5.8!

## install

```
composer require sinkcup/laravel-make-auth-socialite
php artisan make:auth-socialite --force
php artisan migrate
```

## config

add to `config/services.php`:

```
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
    ],
```

add to `.env`:

```
AUTH_SOCIAL_LOGIN_PROVIDERS=Facebook,GitHub,Google
GITHUB_CLIENT_ID=asdf
GITHUB_CLIENT_SECRET=qwer
GITHUB_CALLBACK_URL=http://laravel-demo.localhost/login/github/callback
```

## screenshots

![Laravel Socialite Login page](https://user-images.githubusercontent.com/4971414/59020731-2a17c080-887d-11e9-8cc7-c8c46f97dd1b.png)
![GitHub OAuth Login](https://user-images.githubusercontent.com/4971414/59006611-764f0a80-8855-11e9-9ac9-0f4de8ff6e77.png)
![Laravel Socialite Profile page and Linked Accounts](https://user-images.githubusercontent.com/4971414/59092834-120b7400-8945-11e9-8b1d-ae50c862e6a8.png)
![Laravel Socialite link multiple providers to one user](https://user-images.githubusercontent.com/4971414/59086178-876e4900-8933-11e9-8dad-e2a449a5689e.png)
