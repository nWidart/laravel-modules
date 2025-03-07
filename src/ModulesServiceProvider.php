<?php

namespace Nwidart\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Nwidart\Modules\Providers\ConsoleServiceProvider;
use Nwidart\Modules\Providers\ContractsServiceProvider;

abstract class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot() {}

    /**
     * Register all modules.
     */
    public function register() {}

    /**
     * Register all modules.
     */
    protected function registerModules()
    {
        $manifest = app(ModuleManifest::class);

        (new ProviderRepository($this->app, new Filesystem, $this->getCachedModulePath()))
            ->load($manifest->getProviders());

        $manifest->registerFiles();

    }

    /**
     * Register package's namespaces.
     */
    protected function registerNamespaces()
    {
        $configPath = __DIR__.'/../config/config.php';
        $stubsPath = dirname(__DIR__).'/src/Commands/stubs';

        $this->publishes([
            $configPath => config_path('modules.php'),
        ], 'config');

        $this->publishes([
            $stubsPath => base_path('stubs/nwidart-stubs'),
        ], 'stubs');

        $this->publishes([
            __DIR__.'/../scripts/vite-module-loader.js' => base_path('vite-module-loader.js'),
        ], 'vite');
    }

    /**
     * Register the service provider.
     */
    abstract protected function registerServices();

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [Contracts\RepositoryInterface::class, 'modules'];
    }

    /**
     * Register providers.
     */
    protected function registerProviders()
    {
        $this->app->register(ConsoleServiceProvider::class);
        $this->app->register(ContractsServiceProvider::class);
    }

    protected function getCachedModulePath()
    {
        return Str::replaceLast('services.php', 'modules.php', $this->app->getCachedServicesPath());
    }
}
