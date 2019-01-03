# Laravel Make Auth Socialite

Automatically generate database, pages and routes for Laravel Socialite. Just like `php artisan make:auth`.

supporting Laravel 5.5, 5.6 and 5.7!

## install

```
php artisan make:auth
composer require sinkcup/laravel-make-auth-socialite
php artisan make:auth-socialite --force
php artisan migrate
```

## config

add providers to `config/services.php`:

```
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
    ],
```

ENV:

```
AUTH_SOCIAL_LOGIN_PROVIDERS=Facebook,GitHub,Google
GITHUB_CLIENT_ID=asdf
GITHUB_CLIENT_SECRET=qwer
GITHUB_CALLBACK_URL=http://laravel-demo.localhost/login/github/callback
```

## screenshots

![Login page](https://user-images.githubusercontent.com/4971414/50548717-bac5f100-0c8c-11e9-974a-45dfbe1c41da.png)
![GitHub OAuth Login](https://user-images.githubusercontent.com/4971414/50548725-d3cea200-0c8c-11e9-9b01-9b949bcb6b4d.png)
![logged in](https://user-images.githubusercontent.com/4971414/50548746-24de9600-0c8d-11e9-8262-213ffa1309be.png)
![database](https://user-images.githubusercontent.com/4971414/50548808-f2816880-0c8d-11e9-8227-d8128f040c30.png)
