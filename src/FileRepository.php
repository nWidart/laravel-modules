<?php

namespace Nwidart\Modules;

use Countable;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidAssetPath;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Process\Updater;
use Symfony\Component\Process\Process;

abstract class FileRepository implements Countable, RepositoryInterface
{
    use Macroable;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application|\Laravel\Lumen\Application
     */
    protected $app;

    /**
     * The module path.
     */
    protected ?string $path;

    /**
     * The scanned paths.
     */
    protected array $paths = [];

    /**
     * Stub path
     */
    protected ?string $stubPath = null;

    /**
     * URL Generator
     */
    private UrlGenerator $url;

    /**
     * Config Repository
     */
    private ConfigRepository $config;

    /**
     * File system
     */
    private Filesystem $files;

    /**
     * Cache Manager
     */
    private CacheManager $cache;

    private static $modules = [];

    /**
     * The constructor.
     */
    public function __construct(Container $app, ?string $path = null)
    {
        $this->app = $app;
        $this->path = $path;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->files = $app['files'];
        $this->cache = $app['cache'];
    }

    /**
     * Add other module location.
     */
    public function addLocation(string $path): self
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Get all additional paths.
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get scanned modules paths.
     */
    public function getScanPaths(): array
    {
        $paths = $this->paths;

        $paths[] = $this->getPath();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        $paths = array_map(function ($path) {
            return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
        }, $paths);

        return $paths;
    }

    /**
     * Creates a new Module instance
     */
    abstract protected function createModule(Container $app, string $name, string $path): Module;

    /**
     * Get & scan all modules.
     */
    public function scan(): array
    {
        if (! empty(self::$modules) && ! $this->app->runningUnitTests()) {
            return self::$modules;
        }

        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = (array) $this->getFiles()->glob("{$path}/module.json");

            foreach ($manifests as $manifest) {
                $json = Json::make($manifest);
                $name = $json->get('name');

                $modules[strtolower($name)] = $this->createModule($this->app, $name, dirname($manifest));
            }
        }

        self::$modules = $modules;

        return self::$modules;
    }

    /**
     * Get all modules.
     */
    public function all(): array
    {
        return $this->scan();
    }

    /**
     * Get all modules as collection instance.
     */
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
    }

    /**
     * Get modules by status.
     */
    public function getByStatus($status): array
    {
        $modules = [];

        /** @var Module $module */
        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status)) {
                $modules[$name] = $module;
            }
        }

        return $modules;
    }

    /**
     * Determine whether the given module exist.
     */
    public function has($name): bool
    {
        return array_key_exists(strtolower($name), $this->all());
    }

    /**
     * Get list of enabled modules.
     */
    public function allEnabled(): array
    {
        return $this->getByStatus(true);
    }

    /**
     * Get list of disabled modules.
     */
    public function allDisabled(): array
    {
        return $this->getByStatus(false);
    }

    /**
     * Get count from all modules.
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Get all ordered modules.
     */
    public function getOrdered(string $direction = 'asc'): array
    {
        $modules = $this->allEnabled();

        uasort($modules, function (Module $a, Module $b) use ($direction) {
            if ($a->get('priority') === $b->get('priority')) {
                return 0;
            }

            if ($direction === 'desc') {
                return $a->get('priority') < $b->get('priority') ? 1 : -1;
            }

            return $a->get('priority') > $b->get('priority') ? 1 : -1;
        });

        return $modules;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path('Modules'));
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $name): ?Module
    {
        return $this->all()[strtolower($name)] ?? null;
    }

    /**
     * Find a specific module, if there return that, otherwise throw exception.
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $name): Module
    {
        $module = $this->find($name);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$name}] does not exist!");
    }

    /**
     * Get all modules as laravel collection instance.
     */
    public function collections($status = 1): Collection
    {
        return new Collection($this->getByStatus($status));
    }

    /**
     * Get module path for a specific module.
     */
    public function getModulePath($module): string
    {
        try {
            return $this->findOrFail($module)->getPath().'/';
        } catch (ModuleNotFoundException $e) {
            return $this->getPath().'/'.Str::studly($module).'/';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function assetPath(string $module): string
    {
        return $this->config('paths.assets').'/'.$module;
    }

    /**
     * {@inheritDoc}
     */
    public function config(string $key, $default = null)
    {
        return $this->config->get('modules.'.$key, $default);
    }

    /**
     * Get storage path for module used.
     */
    public function getUsedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->getFiles()->exists($directory) === false) {
            $this->getFiles()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (! $this->getFiles()->exists($path)) {
            $this->getFiles()->put($path, '');
        }

        return $path;
    }

    /**
     * Set module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed($name)
    {
        $module = $this->findOrFail($name);

        $this->getFiles()->put($this->getUsedStoragePath(), $module);

        $module->fireEvent(ModuleEvent::USED);
    }

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed()
    {
        if ($this->getFiles()->exists($this->getUsedStoragePath())) {
            $this->getFiles()->delete($this->getUsedStoragePath());
        }
    }

    /**
     * Get module used for cli session.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function getUsedNow(): string
    {
        return $this->findOrFail($this->getFiles()->get($this->getUsedStoragePath()));
    }

    /**
     * Get laravel filesystem instance.
     */
    public function getFiles(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get module assets path.
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * Get asset url from a specific module.
     *
     * @throws InvalidAssetPath
     */
    public function asset(string $asset): string
    {
        if (Str::contains($asset, ':') === false) {
            throw InvalidAssetPath::missingModuleName($asset);
        }
        [$name, $url] = explode(':', $asset);

        $baseUrl = str_replace(public_path().DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->url->asset($baseUrl."/{$name}/".$url);

        return str_replace(['http://', 'https://'], '//', $url);
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(string $name): bool
    {
        return $this->findOrFail($name)->isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    public function isDisabled(string $name): bool
    {
        return ! $this->isEnabled($name);
    }

    /**
     * Enabling a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function enable(string $name)
    {
        $this->findOrFail($name)->enable();
    }

    /**
     * Disabling a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function disable(string $name)
    {
        $this->findOrFail($name)->disable();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        return $this->findOrFail($name)->delete();
    }

    /**
     * Update dependencies for the specified module.
     */
    public function update(string $module)
    {
        with(new Updater($this))->update($module);
    }

    /**
     * Install the specified module.
     */
    public function install(string $name, string $version = 'dev-master', string $type = 'composer', bool $subtree = false): Process
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }

    /**
     * Get stub path.
     */
    public function getStubPath(): ?string
    {
        if ($this->stubPath !== null) {
            return $this->stubPath;
        }

        if ($this->config('stubs.enabled') === true) {
            return $this->config('stubs.path');
        }

        return $this->stubPath;
    }

    /**
     * Set stub path.
     */
    public function setStubPath(string $stubPath): self
    {
        $this->stubPath = $stubPath;

        return $this;
    }

    public function resetModules(): static
    {
        self::$modules = [];

        return $this;
    }
}
