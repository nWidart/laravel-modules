<?php

namespace Nwidart\Modules;

use Composer\InstalledVersions;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Translation\Translator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\Support\Stub;

class LaravelModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot()
    {
        $this->registerNamespaces();

        $this->app->singleton(
            ModuleManifest::class,
            fn () => new ModuleManifest(
                new Filesystem,
                app(Contracts\RepositoryInterface::class)->getScanPaths(),
                $this->getCachedModulePath(),
                app(ActivatorInterface::class)
            )
        );

        $this->registerModules();

        AboutCommand::add('Laravel-Modules', [
            'Version' => fn () => InstalledVersions::getPrettyVersion('nwidart/laravel-modules'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        $this->registerMigrations();
        $this->registerTranslations();

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'modules');
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath()
    {
        $path = $this->app['config']->get('modules.stubs.path') ?? __DIR__.'/Commands/stubs';
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
    protected function registerServices()
    {
        $this->app->singleton(Contracts\RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new Laravel\LaravelFileRepository($app, $path);
        });
        $this->app->singleton(Contracts\ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.'.$activator)['class'];

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            return new $class($app);
        });
        $this->app->alias(Contracts\RepositoryInterface::class, 'modules');
    }

    protected function registerMigrations(): void
    {
        if (! $this->app['config']->get('modules.auto-discover.migrations', true)) {
            return;
        }

        $this->app->resolving(Migrator::class, function (Migrator $migrator) {
            $migration_path = $this->app['config']->get('modules.paths.generator.migration.path');
            collect(\Nwidart\Modules\Facades\Module::allEnabled())
                ->each(function (\Nwidart\Modules\Laravel\Module $module) use ($migration_path, $migrator) {
                    $migrator->path($module->getExtraPath($migration_path));
                });
        });
    }

    protected function registerTranslations(): void
    {
        if (! $this->app['config']->get('modules.auto-discover.translations', true)) {
            return;
        }
        $this->callAfterResolving('translator', function (TranslatorContract $translator) {
            if (! $translator instanceof Translator) {
                return;
            }

            collect(\Nwidart\Modules\Facades\Module::allEnabled())
                ->each(function (\Nwidart\Modules\Laravel\Module $module) use ($translator) {
                    $path = $module->getExtraPath($this->app['config']->get('modules.paths.generator.lang.path'));
                    $translator->addNamespace($module->getLowerName(), $path);
                    $translator->addJsonPath($path);
                });
        });
    }
}
