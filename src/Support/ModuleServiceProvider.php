<?php

namespace Nwidart\Modules\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Actuator\HealthIndicators;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;

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
     * Health indicators to register with nwidart/laravel-actuator, keyed by indicator name.
     *
     * @var array<string, class-string|array{0: class-string, 1?: array<string, mixed>}>
     */
    protected array $healthIndicators = [];

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
        $this->registerHealthIndicators();
        $generatorMigrationPath = config('modules.paths.generator.migration.path') ?? 'database/migrations';
        $this->loadMigrationsFrom(module_path($this->name, $generatorMigrationPath));
    }

    /**
     * Register the service providers.
     */
    #[\Override]
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
     *
     * Modules that do not override configureSchedules() register nothing. For the others,
     * configureSchedules() runs when something resolves the Schedule (schedule:run,
     * schedule:list, ...), so web requests and other commands never build it. If the
     * Schedule is already resolved once the app has booted, it runs right away. Each new
     * Schedule instance gets the module's events once.
     */
    protected function registerCommandSchedules(): void
    {
        if ((new ReflectionMethod($this, 'configureSchedules'))->getDeclaringClass()->getName() === self::class) {
            return;
        }

        // Wait for boot so configureSchedules() still runs after every provider has booted.
        $this->app->booted(function () {
            $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $this->configureSchedules($schedule));
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
     * Register the module's health indicators with nwidart/laravel-actuator.
     *
     * Each indicator is registered as "module:{nameLower}:{key}", with its definition unchanged.
     * Nothing is registered when the actuator is not installed or modules.actuator.enabled is false,
     * and an indicator the application defines under the same name is kept.
     */
    protected function registerHealthIndicators(): void
    {
        if ($this->healthIndicators === []) {
            return;
        }

        $indicators = [];

        foreach ($this->healthIndicators as $key => $definition) {
            $indicators["module:{$this->nameLower}:{$key}"] = $definition;
        }

        HealthIndicators::register($this->app, $indicators);
    }

    /**
     * Register translations.
     *
     * Published translations (module:publish-translation) live in lang_path('modules/{name}').
     * lang_path() is resources/lang when that directory exists, so apps that published there keep working.
     */
    protected function registerTranslations(): void
    {
        $langPath = lang_path('modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $moduleLangPath = module_path($this->name, config('modules.paths.generator.lang.path'));
            $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($moduleLangPath);
        }
    }

    /**
     * Register config.
     *
     * With cached config, the values come from the cache and publishing only matters to
     * vendor:publish, so outside the console the config directory is not read at all.
     */
    protected function registerConfig(): void
    {
        $runningInConsole = $this->app->runningInConsole();

        if (! $runningInConsole && $this->app->configurationIsCached()) {
            return;
        }

        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $moduleConfigFiles = [];

        foreach ($this->moduleConfigFiles($configPath) as $config => $path) {
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

            if ($runningInConsole) {
                // Publish to the file Laravel's config loader maps back to $key (a.b.c => config/a/b/c.php).
                $publishPath = config_path(str_replace('.', DIRECTORY_SEPARATOR, $key).'.php');
                $this->publishes([$path => $publishPath], 'config');
            }

            $moduleConfigFiles[] = [$key, $path];
        }

        $this->merge_config_from($moduleConfigFiles);
    }

    /**
     * The module's config files, keyed by path relative to the config directory.
     *
     * config.php comes first, then the other files sorted by path. A dedicated file such as
     * settings.php therefore always wins over a "settings" key in config.php, whatever the
     * order the filesystem lists the files in.
     *
     * @return array<string, string>
     */
    private function moduleConfigFiles(string $configPath): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname())] = $file->getPathname();
            }
        }

        uksort($files, fn (string $a, string $b) => [$a !== 'config.php', $a] <=> [$b !== 'config.php', $b]);

        return $files;
    }

    /**
     * Merge the module's config files into the application config.
     *
     * The module's files are merged first, in order, so a later file wins over an earlier one
     * for the same key. Values the application already has under the module's key (for example
     * a config/blog.php published with module:publish-config) then win over the module's values,
     * like Laravel's mergeConfigFrom(), but recursively (see mergeApplicationConfig()).
     *
     * @param  array<int, array{string, string}>  $files  Config key and file path pairs.
     */
    private function merge_config_from(array $files): void
    {
        if (app()->configurationIsCached()) {
            return;
        }

        $moduleConfig = [];

        foreach ($files as [$key, $path]) {
            Arr::set($moduleConfig, $key, array_replace_recursive(Arr::get($moduleConfig, $key, []), require $path));
        }

        foreach ($moduleConfig as $key => $values) {
            config([$key => $this->mergeApplicationConfig($values, config($key, []))]);
        }
    }

    /**
     * Merge the application's config values over the module's.
     *
     * Where both values are associative arrays, they merge key by key, recursively. Otherwise the
     * application's value replaces the module's as a whole, lists and empty arrays included, so a
     * published config can remove items from a list or clear a value with [].
     *
     * @param  array<mixed>  $moduleConfig
     * @param  array<mixed>  $appConfig
     * @return array<mixed>
     */
    private function mergeApplicationConfig(array $moduleConfig, array $appConfig): array
    {
        foreach ($appConfig as $key => $value) {
            $moduleValue = $moduleConfig[$key] ?? null;

            $moduleConfig[$key] = $this->isAssociativeArray($value) && $this->isAssociativeArray($moduleValue)
                ? $this->mergeApplicationConfig($moduleValue, $value)
                : $value;
        }

        return $moduleConfig;
    }

    /**
     * @phpstan-assert-if-true array<mixed> $value
     */
    private function isAssociativeArray(mixed $value): bool
    {
        return is_array($value) && ! array_is_list($value);
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $sourcePath = module_path($this->name, config('modules.paths.generator.views.path'));

        // Publish groups are only read by vendor:publish.
        if ($this->app->runningInConsole()) {
            $viewPath = resource_path('views/modules/'.$this->nameLower);
            $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        }

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the paths where the module views are published.
     *
     * @return array<int, string>
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
