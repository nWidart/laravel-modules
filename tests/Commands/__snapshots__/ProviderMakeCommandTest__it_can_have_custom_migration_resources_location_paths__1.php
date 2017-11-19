<?php return '<?php

namespace Modules\\Blog\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Database\\Eloquent\\Factory;

class BlogServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

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
        $this->loadMigrationsFrom(__DIR__ . \'/../migrations\');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.\'/../Config/config.php\' => config_path(\'blog.php\'),
        ], \'config\');
        $this->mergeConfigFrom(
            __DIR__.\'/../Config/config.php\', \'blog\'
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

        $sourcePath = __DIR__.\'/../Resources/views\';

        $this->publishes([
            $sourcePath => $viewPath
        ],\'views\');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . \'/modules/blog\';
        }, \\Config::get(\'view.paths\')), [$sourcePath]), \'blog\');
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
            $this->loadTranslationsFrom(__DIR__ .\'/../Resources/lang\', \'blog\');
        }
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment(\'production\')) {
            app(Factory::class)->load(__DIR__ . \'/../Database/factories\');
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
