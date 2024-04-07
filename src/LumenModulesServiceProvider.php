<?php

namespace Nwidart\Modules;

use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Lumen\LumenFileRepository;
use Nwidart\Modules\Support\Stub;

class LumenModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot(): void
    {
        $this->setupStubPath();
    }

    /**
     * Register all modules.
     */
    public function register(): void
    {
        $this->registerNamespaces();
        $this->registerServices();
        $this->registerModules();
        $this->registerProviders();
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath(): void
    {
        Stub::setBasePath(__DIR__ . '/Commands/stubs');

        if (app('modules')->config('stubs.enabled') === true) {
            Stub::setBasePath(app('modules')->config('stubs.path'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(): void
    {
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new LumenFileRepository($app, $path);
        });

        $this->app->singleton(ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.' . $activator)['class'];

            return new $class($app);
        });

        $this->app->alias(RepositoryInterface::class, 'modules');
    }
}
