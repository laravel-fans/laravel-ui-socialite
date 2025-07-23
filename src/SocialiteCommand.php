<?php

namespace LaravelFans\UiSocialite;

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
                    { kit=vue : The preset starter kit (vue) }
                    {--pages : Only scaffold the socialite pages}
                    {--force : Overwrite existing pages by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold socialite login pages and routes';

    /**
     * The pages that need to be exported.
     *
     * @var array
     */
    protected $pages = [
        'auth/Login.vue.stub' => 'auth/Login.vue',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('kit'))) {
            return call_user_func(static::$macros[$this->argument('kit')], $this);
        }

        if (! in_array($this->argument('kit'), ['vue'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->ensureDirectoriesExist();

        $this->exportPages();

        if (! $this->option('pages')) {
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
        if (! is_dir($directory = $this->getPagePath('auth'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getPagePath('settings'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = app_path('Http/Controllers/Settings'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication pages.
     *
     * @return void
     */
    protected function exportPages()
    {
        foreach ($this->pages as $key => $value) {
            if (file_exists($view = $this->getPagePath($value)) && ! $this->option('force')) {
                if (! $this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            $path = __DIR__ . '/stubs/resources/' . $this->argument('kit') . '/js/pages/';
            copy($path . $key, $view);
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
            app_path('Http/Controllers/Auth/AuthenticatedSessionController.php'),
            $this->compileControllerStub('auth/AuthenticatedSessionController.stub')
        );
        file_put_contents(
            app_path('Http/Controllers/Settings/ProfileController.php'),
            $this->compileControllerStub('settings/ProfileController.stub')
        );
        copy(
            __DIR__ . '/stubs/tests/TestCase.stub',
            app_path() . '/../tests/TestCase.php'
        );

        $web_routes = file_get_contents(base_path('routes/auth.php'));
        foreach (explode("\n", file_get_contents(__DIR__ . '/stubs/routes.stub')) as $line) {
            if (empty($line)) {
                continue;
            }
            if (strpos($web_routes, $line) === false) {
                file_put_contents(
                    base_path('routes/auth.php'),
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
    protected function compileControllerStub(string $path)
    {
        return $this->compileStub('controllers/' . $path);
    }

    /**
     * Compiles Controller stub.
     *
     * @param $path string
     * @return string
     */
    protected function compileStub(string $path)
    {
        return str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace(),
            file_get_contents(__DIR__ . '/stubs/' . $path)
        );
    }

    /**
     * Get full view path relative to the application's configured view path.
     *
     * @param string $path
     * @return string
     */
    protected function getPagePath(string $path)
    {
        return implode(DIRECTORY_SEPARATOR, [resource_path('js/pages'), $path]);
    }
}
