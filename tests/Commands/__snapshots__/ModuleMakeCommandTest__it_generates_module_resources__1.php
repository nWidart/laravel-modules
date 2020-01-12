<?php return '<?php

namespace Modules\\Blog\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Database\\Eloquent\\Factory;

class BlogServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(module_path(\'Blog\', \'Database/Migrations\'));
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
            module_path(\'Blog\', \'Config/config.php\') => config_path(\'blog.php\'),
        ], \'config\');
        $this->mergeConfigFrom(
            module_path(\'Blog\', \'Config/config.php\'), \'blog\'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path(\'views/modules/blog\');

        $sourcePath = module_path(\'Blog\', \'Resources/views\');

        $this->publishes([
            $sourcePath => $viewPath
        ], [\'views\', \'blog-module-views\']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), \'blog\');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path(\'lang/modules/blog\');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, \'blog\');
        } else {
            $this->loadTranslationsFrom(module_path(\'Blog\', \'Resources/lang\'), \'blog\');
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
            app(Factory::class)->load(module_path(\'Blog\', \'Database/factories\'));
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

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\\Config::get(\'view.paths\') as $path) {
            if (is_dir($path . \'/modules/blog\')) {
                $paths[] = $path . \'/modules/blog\';
            }
        }
        return $paths;
    }
}
';
