<?php

namespace Nwidart\Modules\Laravel;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Str;
use Nwidart\Modules\Module as BaseModule;

class Module extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getCachedServicesPath(): string
    {
        // This checks if we are running on a Laravel Vapor managed instance
        // and sets the path to a writable one (services path is not on a writable storage in Vapor).
        if (config('modules.vapor_maintenance_mode')) {
            return Str::replaceLast('config.php', $this->getSnakeName().'_module.php', $this->getAppCachePath('config.php'));
        }

        return Str::replaceLast('services.php', $this->getSnakeName().'_module.php', $this->getAppCachePath('services.php'));
    }

    /**
     * Get the path of a Laravel cache file (config.php or services.php) of the application.
     *
     * The Application contract has no cache path methods, so applications that only implement
     * the contract get the file in Laravel's default cache directory.
     */
    private function getAppCachePath(string $file): string
    {
        $app = $this->app;

        if ($app instanceof Application) {
            return $file === 'config.php' ? $app->getCachedConfigPath() : $app->getCachedServicesPath();
        }

        return $app->bootstrapPath('cache/'.$file);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function registerProviders(): void
    {
        (new ProviderRepository($this->app, new Filesystem, $this->getCachedServicesPath()))
            ->load($this->get('providers', []));
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function registerAliases(): void
    {
        $loader = AliasLoader::getInstance();
        foreach ($this->get('aliases', []) as $aliasName => $aliasClass) {
            $loader->alias($aliasName, $aliasClass);
        }
    }
}
