<?php

namespace Nwidart\Modules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Providers\ConsoleServiceProvider;

abstract class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     *
     * @return void
     */
    public function boot() {}

    /**
     * Register all modules.
     *
     * @return void
     */
    #[\Override]
    public function register() {}

    /**
     * Register all modules.
     *
     * @return void
     */
    protected function registerModules()
    {
        $manifest = app(ModuleManifest::class);

        $manifest->registerAliases();

        (new ProviderRepository($this->app, new Filesystem, $this->getCachedModulePath()))
            ->load($manifest->getProviders());

        $manifest->registerFiles();

    }

    /**
     * Register package's namespaces.
     *
     * @return void
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
     *
     * @return void
     */
    abstract protected function registerServices();

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    #[\Override]
    public function provides(): array
    {
        return [Contracts\RepositoryInterface::class, 'modules'];
    }

    /**
     * Register providers.
     *
     * @return void
     */
    protected function registerProviders()
    {
        $this->app->register(ConsoleServiceProvider::class);
    }

    /**
     * Get the path of the module providers manifest.
     *
     * The file is always named modules.php and placed in the directory of a Laravel cache file,
     * so it can never take the name of that cache file (APP_SERVICES_CACHE / APP_CONFIG_CACHE may use any name).
     *
     * @return string
     */
    protected function getCachedModulePath()
    {
        // On Laravel Vapor the bootstrap cache directory is read-only, but the config cache is
        // written at runtime to a writable location, so keep the module manifest next to it.
        $vapor = $this->app->make(ConfigRepository::class)->get('modules.vapor_maintenance_mode');

        if ($this->app instanceof Application) {
            $cacheFile = $vapor ? $this->app->getCachedConfigPath() : $this->app->getCachedServicesPath();
        } else {
            // The Application contract has no cache path methods: use Laravel's default cache directory.
            $cacheFile = $this->app->bootstrapPath($vapor ? 'cache/config.php' : 'cache/services.php');
        }

        return dirname($cacheFile).DIRECTORY_SEPARATOR.'modules.php';
    }
}
