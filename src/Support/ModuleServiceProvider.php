<?php

namespace Nwidart\Modules\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class ModuleServiceProvider extends ServiceProvider
{
    use PathNamespace;

    /**
     * The name of the module.
     */
    protected string $name;

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower;

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [];

    /**
     * Create a new service provider instance.
     */
    public function __construct($app)
    {
        if (! isset($this->name, $this->nameLower)) {
            throw new \LogicException('Module service provider must define both $name and $nameLower properties.');
        }

        parent::__construct($app);
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $generatorMigrationPath = config('modules.paths.generator.migration.path') ?? 'database/migrations';
        $this->loadMigrationsFrom(module_path($this->name, $generatorMigrationPath));
    }

    /**
     * Register the service providers.
     */
    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands($this->commands);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        if (! method_exists($this, 'configureSchedules')) {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $this->configureSchedules($schedule);
        });
    }

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        //
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $moduleLangPath = module_path($this->name, config('modules.paths.generator.lang.path'));
            $this->loadTranslationsFrom($moduleLangPath);
            $this->loadJsonTranslationsFrom($moduleLangPath);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = str_replace(DIRECTORY_SEPARATOR, '.', $config);
                    $configKey = str_replace('.php', '', $configKey);

                    $segments = explode('.', $this->nameLower.'.'.$configKey);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);
                    $publishPath = ($config === 'config.php') ? config_path($this->nameLower.'.php') : config_path($config);
                    $this->publishes([$file->getPathname() => $publishPath], 'config');

                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    private function merge_config_from(string $path, string $key): void
    {
        if (app()->configurationIsCached()) {
            return;
        }

        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, config('modules.paths.generator.views.path'));

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the paths where the module views are published.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
