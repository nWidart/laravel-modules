<?php

namespace Nwidart\Modules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Nwidart\Modules\Support\Stub;

class LumenModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     *
     * @return void
     */
    #[\Override]
    public function boot()
    {
        $this->setupStubPath();
    }

    /**
     * Register all modules.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        $this->registerNamespaces();
        $this->registerServices();
        $this->registerModules();
        $this->registerProviders();
    }

    /**
     * Setup stub path.
     *
     * @return void
     */
    public function setupStubPath()
    {
        Stub::setBasePath(__DIR__.'/Commands/stubs');

        /** @var Contracts\RepositoryInterface $modules */
        $modules = app('modules');

        if ($modules->config('stubs.enabled') === true) {
            Stub::setBasePath($modules->config('stubs.path'));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[\Override]
    protected function registerServices()
    {
        $this->app->singleton(Contracts\RepositoryInterface::class, fn ($app) => new Lumen\LumenFileRepository($app));
        $this->app->singleton(Contracts\ActivatorInterface::class, function (Container $app) {
            $config = $app->make(ConfigRepository::class);
            $activator = $config->get('modules.activator');
            $class = $config->get('modules.activators.'.$activator)['class'];

            return new $class($app);
        });
        $this->app->alias(Contracts\RepositoryInterface::class, 'modules');
    }
}
