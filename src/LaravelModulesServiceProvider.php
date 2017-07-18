<?php

namespace Nwidart\Modules;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Providers\BootstrapServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Providers\ContractsServiceProvider;
use Nwidart\Modules\Support\Stub;

class LaravelModulesServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Booting the package.
     */
    public function boot()
    {
        $this->registerNamespaces();

        $this->registerModules();
    }

    /**
     * Register all modules.
     */
    protected function registerModules()
    {
        $this->app->register(BootstrapServiceProvider::class);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        // Lumen doesn't support boot()
        if (get_class($this->app) == "Laravel\Lumen\Application") {
            $this->registerNamespaces();
            $this->registerModules();
        }
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath()
    {
        Stub::setBasePath(__DIR__ . '/Commands/stubs');

        switch (get_class($this->app)) {
            // // Lumen doesn't support boot()
            case "Laravel\Lumen\Application":
                if (app('modules')->config('stubs.enabled') === true) {
                    Stub::setBasePath(app('modules')->config('stubs.path'));
                }
                break;
            default:
                $this->app->booted(function ($app) {
                    if ($app['modules']->config('stubs.enabled') === true) {
                        Stub::setBasePath($app['modules']->config('stubs.path'));
                    }
                });
                break;
        }
    }

    /**
     * Register package's namespaces.
     */
    protected function registerNamespaces()
    {
        $configPath = __DIR__ . '/../config/config.php';

        $this->mergeConfigFrom($configPath, 'modules');
        $this->publishes([
            $configPath => config_path('modules.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    protected function registerServices()
    {
        $this->app->singleton('modules', function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new Repository($app, $path);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['modules'];
    }

    /**
     * Register providers.
     */
    protected function registerProviders()
    {
        $this->app->register(ConsoleServiceProvider::class);
        $this->app->register(ContractsServiceProvider::class);
    }
}
