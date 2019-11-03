<?php return '<?php

namespace Modules\\ModuleName\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Database\\Eloquent\\Factory;

class ModuleNameServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path(\'ModuleName\', \'Database/Migrations\'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path(\'ModuleName\', \'Config/config.php\') => config_path(\'modulename.php\'),
        ], \'config\');
        $this->mergeConfigFrom(
            module_path(\'ModuleName\', \'Config/config.php\'), \'modulename\'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path(\'views/modules/modulename\');

        $sourcePath = module_path(\'ModuleName\', \'Resources/views\');

        $this->publishes([
            $sourcePath => $viewPath
        ],\'views\');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . \'/modules/modulename\';
        }, \\Config::get(\'view.paths\')), [$sourcePath]), \'modulename\');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path(\'lang/modules/modulename\');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, \'modulename\');
        } else {
            $this->loadTranslationsFrom(module_path(\'ModuleName\', \'Resources/lang\'), \'modulename\');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment(\'production\') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path(\'ModuleName\', \'Database/factories\'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
';
