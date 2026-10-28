<?php

namespace Nwidart\Modules\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
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

            $this->merge_config_from($path, $key);
        }
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
