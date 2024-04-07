<?php

namespace Nwidart\Modules;

use Composer\InstalledVersions;
use Illuminate\Foundation\Console\AboutCommand;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Support\Stub;

class LaravelModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot(): void
    {
        $this->registerNamespaces();
        $this->registerModules();

        AboutCommand::add('Laravel-Modules', [
            'Version' => fn () => InstalledVersions::getVersion('nwidart/laravel-modules'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath(): void
    {
        $path = $this->app['config']->get('modules.stubs.path') ?? __DIR__ . '/Commands/stubs';

        Stub::setBasePath($path);

        $this->app->booted(function ($app) {
            /** @var RepositoryInterface $moduleRepository */
            $moduleRepository = $app[RepositoryInterface::class];
            if ($moduleRepository->config('stubs.enabled') === true) {
                Stub::setBasePath($moduleRepository->config('stubs.path'));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(): void
    {
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new LaravelFileRepository($app, $path);
        });

        $this->app->singleton(ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.' . $activator)['class'];

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            return new $class($app);
        });

        $this->app->alias(RepositoryInterface::class, 'modules');
    }
}
