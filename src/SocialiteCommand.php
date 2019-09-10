<?php

namespace sinkcup\LaravelUiSocialite;

use InvalidArgumentException;
use Illuminate\Console\Command;
use PHLAK\SemVer\Version;

class SocialiteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ui:socialite
                    { type=bootstrap : The preset type (bootstrap) }
                    {--views : Only scaffold the socialite views}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold socialite login views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'settings/profile.stub' => 'settings/profile.blade.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        if (! in_array($this->argument('type'), ['bootstrap'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->ensureDirectoriesExist();

        $this->exportViews();

        if (! $this->option('views')) {
            $this->exportBackend();
        }

        $this->info('Socialite scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function ensureDirectoriesExist()
    {
        if (! is_dir($directory = $this->getViewPath('auth'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('settings'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = app_path('Http/Controllers/Settings'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            if (file_exists($view = $this->getViewPath($value)) && ! $this->option('force')) {
                if (! $this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            $version = new Version(app()->version());
            $path = __DIR__ . '/Socialite/' . $this->argument('type') . '-stubs/';
            $file_for_version = $key . '_' . $version->major . '.' . $version->minor;
            $file_path = file_exists($path . $file_for_version) ? $path . $file_for_version : $path . $key;
            copy($file_path, $view);
        }
    }

    /**
     * Export the authentication backend.
     *
     * @return void
     */
    protected function exportBackend()
    {
        file_put_contents(
            app_path('Http/Controllers/Auth/LoginController.php'),
            $this->compileControllerStub('auth/LoginController.stub')
        );
        file_put_contents(
            app_path('Http/Controllers/Settings/ProfileController.php'),
            $this->compileControllerStub('settings/ProfileController.stub')
        );
        copy(
            __DIR__ . '/Socialite/stubs/database/factories/SocialAccountFactory.stub',
            database_path('factories/SocialAccountFactory.php')
        );
        copy(
            __DIR__ . '/Socialite/stubs/tests/TestCase.stub',
            app_path() . '/../tests/TestCase.php'
        );

        $web_routes = file_get_contents(base_path('routes/web.php'));
        foreach (explode("\n", file_get_contents(__DIR__ . '/Socialite/stubs/routes.stub')) as $line) {
            if (empty($line)) {
                continue;
            }
            if (strpos($line, 'Auth::routes(') !== false) {
                $web_routes = str_replace('Auth::routes();', $line, file_get_contents(base_path('routes/web.php')));
                file_put_contents(base_path('routes/web.php'), $web_routes);
            } elseif (strpos($web_routes, $line) === false) {
                file_put_contents(
                    base_path('routes/web.php'),
                    $line . "\n",
                    FILE_APPEND
                );
            }
        }
    }

    /**
     * Compiles Controller stub.
     *
     * @param $path string
     * @return string
     */
    protected function compileControllerStub($path)
    {
        return $this->compileStub('controllers/' . $path);
    }

    /**
     * Compiles Controller stub.
     *
     * @param $path string
     * @return string
     */
    protected function compileStub($path)
    {
        return str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace(),
            file_get_contents(__DIR__ . '/Socialite/stubs/' . $path)
        );
    }

    /**
     * Get full view path relative to the application's configured view path.
     *
     * @param  string  $path
     * @return string
     */
    protected function getViewPath($path)
    {
        return implode(DIRECTORY_SEPARATOR, [
            config('view.paths')[0] ?? resource_path('views'), $path,
        ]);
    }
}
