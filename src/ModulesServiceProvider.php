<?php

namespace Nwidart\Modules;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Providers\BootstrapServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Providers\ContractsServiceProvider;

abstract class ModulesServiceProvider extends ServiceProvider
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
    }

    /**
     * Register all modules.
     */
    public function register()
    {
    }

    /**
     * Register all modules.
     */
    protected function registerModules()
    {
        $this->app->register(BootstrapServiceProvider::class);
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
    abstract protected function registerServices();

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
