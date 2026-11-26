<?php

namespace Nwidart\Modules;

use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Nwidart\Actuator\Actuator;
use Nwidart\Modules\Actuator\HealthIndicators;
use Nwidart\Modules\Actuator\ModulesEndpoint;
use Nwidart\Modules\Actuator\ModulesHealthIndicator;
use Nwidart\Modules\Actuator\ScheduledTaskModules;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidActivatorClass;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Support\ListenerClassNameGuesser;
use Nwidart\Modules\Support\Stub;

class LaravelModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     *
     * @return void
     */
    #[\Override]
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerNamespaces();

            AboutCommand::add('Laravel-Modules', [
                'Version' => fn () => InstalledVersions::getPrettyVersion('nwidart/laravel-modules'),
            ]);
        }

        $this->callAfterResolving('blade.compiler', fn (BladeCompiler $blade) => $blade->if('module', fn (string $name) => module($name)));

        HealthIndicators::register($this->app, ['modules' => ModulesHealthIndicator::class]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'modules');

        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerEventDiscovery();
        $this->registerActuatorEndpoint();

        $this->registerModules();
    }

    /**
     * Setup stub path.
     *
     * The custom `modules.stubs.path` is used only when `modules.stubs.enabled` is true,
     * otherwise the package stubs are used. The path is resolved once the app is booted,
     * so the merged package config and config changed by other providers are taken into account.
     *
     * @return void
     */
    public function setupStubPath()
    {
        $this->app->booted(function (Application $app) {
            $config = $app->make(ConfigRepository::class);
            $path = $config->get('modules.stubs.enabled') === true ? $config->get('modules.stubs.path') : null;

            Stub::setBasePath(is_string($path) && $path !== '' ? $path : __DIR__.'/Commands/stubs');
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    #[\Override]
    protected function registerServices()
    {
        // No fixed path: the repository is resolved during register(), and getPath() must keep
        // reading modules.paths.modules lazily so later config changes are still picked up.
        $this->app->singleton(RepositoryInterface::class, fn ($app) => new Laravel\LaravelFileRepository($app));
        $this->app->singleton(ActivatorInterface::class, function (Application $app) {
            $config = $app->make(ConfigRepository::class);
            $activator = $config->get('modules.activator');
            $activators = $config->get('modules.activators');

            if (! is_string($activator) || ! is_array($activators) || ! is_array($activators[$activator] ?? null)) {
                throw InvalidActivatorClass::unknownActivator($activator);
            }

            $class = $activators[$activator]['class'] ?? null;

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            if (! class_exists($class)) {
                throw InvalidActivatorClass::classNotFound($activator, $class);
            }

            return new $class($app);
        });
        $this->app->alias(RepositoryInterface::class, 'modules');
        $this->registerBrokenModules();

        $this->app->singleton(
            ModuleManifest::class,
            fn () => new ModuleManifest(
                new Filesystem,
                app(RepositoryInterface::class)->getScanPaths(),
                $this->getCachedModulePath(),
                app(ActivatorInterface::class)
            )
        );

    }

    protected function registerMigrations(): void
    {
        if (! $this->app->make(ConfigRepository::class)->get('modules.auto-discover.migrations', true)) {
            return;
        }

        $this->app->resolving(Migrator::class, function (Migrator $migrator) {
            $migration_path = $this->app->make(ConfigRepository::class)->get('modules.paths.generator.migration.path');
            collect(Module::allEnabled())
                ->each(function (\Nwidart\Modules\Module $module) use ($migration_path, $migrator) {
                    $migrator->path($module->getExtraPath($migration_path));
                });
        });
    }

    /**
     * Let Laravel event discovery find listeners inside modules.
     *
     * Only the class name guessing changes: no directory is scanned and no discovery path is added.
     * The guesser reads config on its first call, so this costs nothing when events are cached.
     */
    protected function registerEventDiscovery(): void
    {
        ListenerClassNameGuesser::register();
    }

    /**
     * Register the "modules" endpoint of nwidart/laravel-actuator, when a version with custom endpoints is installed.
     * With a version that tags scheduled tasks, also add the "module" tag to the tasks of each module.
     *
     * The actuator keeps its endpoints per application and rejects a duplicate id, so a second
     * register() on the same application (a forced re-register) must not add the endpoint again.
     */
    protected function registerActuatorEndpoint(): void
    {
        if (! class_exists(Actuator::class) || $this->app->getProviders(self::class) !== []) {
            return;
        }

        Actuator::endpoint('modules', ModulesEndpoint::class);

        // Reflection, not method_exists(): static analysis sees one installed actuator version
        // and would call the check always true or always false.
        if ((new \ReflectionClass(Actuator::class))->hasMethod('tagScheduledTasksUsing')) {
            $this->app->singletonIf(ScheduledTaskModules::class);
            Actuator::tagScheduledTasksUsing(fn (Event $event): array => $this->app->make(ScheduledTaskModules::class)->tags($event));
        }
    }

    protected function registerTranslations(): void
    {
        if (! $this->app->make(ConfigRepository::class)->get('modules.auto-discover.translations')) {
            return;
        }
        $this->callAfterResolving('translator', function (TranslatorContract $translator) {
            if (! $translator instanceof Translator) {
                return;
            }

            collect(Module::allEnabled())
                ->each(function (\Nwidart\Modules\Module $module) use ($translator) {
                    $path = $module->getExtraPath($this->app->make(ConfigRepository::class)->get('modules.paths.generator.lang.path'));
                    $translator->addNamespace($module->getLowerName(), $path);
                    $translator->addJsonPath($path);
                });
        });
    }
}
