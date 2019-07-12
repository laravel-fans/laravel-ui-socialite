<?php

namespace sinkcup\LaravelMakeAuthSocialite\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use PHLAK\SemVer\Version;

class MakeAuthSocialite extends Command
{
    use DetectsApplicationNamespace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth-socialite
                    {--views : Only scaffold the authentication views}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold socialite login database, views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'user/profile_edit.stub' => 'user/profile_edit.blade.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();

        $this->exportViews();

        if (! $this->option('views')) {
            file_put_contents(
                app_path('Http/Controllers/Auth/LoginController.php'),
                $this->compileControllerStub('auth/LoginController.stub')
            );
            file_put_contents(
                app_path('Http/Controllers/ProfileController.php'),
                $this->compileControllerStub('ProfileController.stub')
            );
            copy(
                __DIR__.'/stubs/make/database/factories/SocialAccountFactory.php',
                database_path('factories/SocialAccountFactory.php')
            );
            file_put_contents(
                app_path().'/../tests/Feature/ProfileControllerTest.php',
                $this->compileStub('tests/Feature/ProfileControllerTest.stub')
            );

            $web_routes = file_get_contents(base_path('routes/web.php'));
            foreach (explode("\n", file_get_contents(__DIR__ . '/stubs/make/routes.stub')) as $line) {
                if (empty($line)) {
                    continue;
                }
                if (strpos($line, 'Auth::routes(') !== false) {
                    $web_routes = str_replace('Auth::routes();', $line, file_get_contents(base_path('routes/web.php')));
                    file_put_contents(base_path('routes/web.php'), $web_routes);
                }
                if (strpos($web_routes, $line) === false) {
                    file_put_contents(
                        base_path('routes/web.php'),
                        $line . "\n",
                        FILE_APPEND
                    );
                }
            }
        }

        $this->info('Authentication scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (! is_dir($directory = resource_path('views/auth'))) {
            mkdir($directory, 0755, true);
        }
        if (! is_dir($directory = resource_path('views/user'))) {
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
            if (file_exists($view = resource_path('views/'.$value)) && ! $this->option('force')) {
                if (! $this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }
            $version = new Version(app()->version());
            $path = __DIR__.'/stubs/make/views/';
            $file_for_version = $key.'_'.$version->major.'.'.$version->minor;
            $file_path = file_exists($path.$file_for_version) ? $path.$file_for_version : $path.$key;
            copy($file_path, $view);
        }
    }

    /**
     * Compiles Controller stub.
     *
     * @return string
     */
    protected function compileControllerStub($stub)
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__.'/stubs/make/controllers/' . $stub)
        );
    }

    /**
     * Compiles Controller stub.
     *
     * @return string
     */
    protected function compileStub($path)
    {
        return str_replace(
            '{{namespace}}',
            $this->getAppNamespace(),
            file_get_contents(__DIR__.'/stubs/make/' . $path)
        );
    }

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
}
