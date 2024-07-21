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
     *
     * @var string|null
     */
    protected $path;

    /**
     * The scanned paths.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * @var string
     */
    protected $stubPath;

    /**
     * @var UrlGenerator
     */
    private $url;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var CacheManager
     */
    private $cache;

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
     * {@inheritDoc}
     */
    public function boot(): void
    {
        foreach ($this->ordered() as $module) {
            $module->boot();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        foreach ($this->ordered() as $module) {
            $module->register();
        }
    }

    /**
     * Creates a new Module instance
     *
     * @param  Container  $app
     * @param  string  $args
     * @param  string  $path
     */
    abstract protected function module(...$args): Module;

    /**
     * @deprecated 10.0.11 use module()
     */
    protected function createModule(...$args)
    {
        return $this->module(...$args);
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
     * {@inheritDoc}
     */
    public function all(?bool $enabled = null): array
    {
        if (is_bool($enabled)) {
            if ($enabled) {
                return $this->status(true);
            }

            return $this->status(false);
        }

        if (!$this->config('cache.enabled')) {
            return $this->scan();
        }

        return $this->formatCached($this->cached());
    }

    /**
     * @deprecated 10.0.11 use all(true) or status(true)
     */
    public function allEnabled(): array
    {
        return $this->status(true);
    }

    /**
     * @deprecated 10.0.11 use all(false) or status(false)
     */
    public function allDisabled(): array
    {
        return $this->status(false);
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $name): ?Module
    {
        foreach ($this->all() as $module) {
            if ($module->getLowerName() === strtolower($name)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
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
     * Get modules by the given status.
     */
    public function status(bool $status): array
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
     * @deprecated 10.0.11 use status()
     */
    public function getByStatus($status): array
    {
        return $this->status($status);
    }

    /**
     * {@inheritDoc}
     */
    public function scan(): array
    {
        $paths = $this->scanPaths();

        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = $this->files()->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = Json::make($manifest)->get('name');

                $modules[$name] = $this->module($this->app, $name, dirname($manifest));
            }
        }

        return $modules;
    }

    /**
     * {@inheritDoc}
     */
    public function scanPaths(): array
    {
        $paths = $this->paths;

        $paths[] = $this->path();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        $paths = array_map(function ($path) {
            return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
        }, $paths);

        return $paths;
    }

    /**
     * @deprecated 10.0.11 use scanPaths()
     */
    public function getScanPaths(): array
    {
        return $this->scanPaths();
    }

    /**
     * {@inheritDoc}
     */
    public function cached(): array
    {
        return $this->cache->store($this->config->get('modules.cache.driver'))->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * @deprecated 10.0.11 use cached()
     */
    public function getCached(): array
    {
        return $this->cached();
    }

    /**
     * Format the cached data as array of modules.
     */
    protected function formatCached(array $cached): array
    {
        $modules = [];

        foreach ($cached as $name => $module) {
            $path = $module['path'];

            $modules[$name] = $this->module($this->app, $name, $path);
        }

        return $modules;
    }

    /**
     * {@inheritDoc}
     */
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
    }

    /**
     * Get all modules as laravel collection instance.
     */
    public function collect(?bool $status = true): Collection
    {
        return new Collection($this->all((bool) $status));
    }

    /**
     * Get all modules as laravel collection instance.
     */
    public function collections(?bool $status = true): Collection
    {
        return new Collection($this->all((bool) $status));
    }

    /**
     * {@inheritDoc}
     */
    public function ordered(string $sort = 'asc'): array
    {
        $modules = $this->all(true);

        uasort($modules, function (Module $a, Module $b) use ($sort) {
            if ($a->get('priority') === $b->get('priority')) {
                return 0;
            }

            if ($sort === 'desc') {
                return $a->get('priority') < $b->get('priority') ? 1 : -1;
            }

            return $a->get('priority') > $b->get('priority') ? 1 : -1;
        });

        return $modules;
    }

    /**
     * @deprecated 10.0.11 use ordered()
     */
    public function getOrdered(string $direction = 'asc'): array
    {
        return $this->ordered($direction);
    }

    /**
     * Update dependencies for the specified module.
     */
    public function update(string $module): void
    {
        with(new Updater($this))->update($module);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        return $this->findOrFail($name)->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function config(string $key, ?string $default = null): mixed
    {
        return $this->config->get('modules.' . $key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function count(?bool $enabled = null): int
    {
        if (is_bool($enabled)) {
            if ($enabled) {
                return count($this->all(true));
            }

            return count($this->all(false));
        }

        return count($this->all());
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * Enabling a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function enable(string $name): void
    {
        $this->findOrFail($name)->enable();
    }

    /**
     * {@inheritDoc}
     */
    public function enabled(string $name): bool
    {
        return $this->findOrFail($name)->isEnabled();
    }

    /**
     * @deprecated 10.0.11 use enabled()
     */
    public function isEnabled(string $name): bool
    {
        return $this->enabled($name);
    }

    /**
     * Disabling a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function disable(string $name): void
    {
        $this->findOrFail($name)->disable();
    }

    /**
     * {@inheritDoc}
     */
    public function disabled(string $name): bool
    {
        return !$this->enabled($name);
    }

    /**
     * @deprecated 10.0.11 use disabled()
     */
    public function isDisabled(string $name): bool
    {
        return $this->disabled($name);
    }

    /**
     * {@inheritDoc}
     */
    public function files(): Filesystem
    {
        return $this->files;
    }

    /**
     * @deprecated 10.0.11 use files()
     */
    public function getFiles(): Filesystem
    {
        return $this->files();
    }

    /**
     * {@inheritDoc}
     */
    public function path(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path('Modules'));
    }

    /**
     * @deprecated 10.0.11 use path()
     */
    public function getPath(): string
    {
        return $this->path();
    }

    /**
     * Get all additional paths.
     */
    public function extra_paths(): array
    {
        return $this->paths;
    }

    /**
     * @deprecated 10.0.11 use extra_paths()
     */
    public function getPaths(): array
    {
        return $this->extra_paths();
    }

    /**
     * {@inheritDoc}
     */
    public function modulePath($module): string
    {
        try {
            return $this->findOrFail($module)->getPath() . '/';
        } catch (ModuleNotFoundException $e) {
            return $this->path() . '/' . Str::studly($module) . '/';
        }
    }

    /**
     * @deprecated 10.0.11 use modulePath()
     */
    public function getModulePath($module): string
    {
        return $this->modulePath($module);
    }

    /**
     * Add extra module path.
     */
    public function add_path(string $path): self
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * @deprecated 10.0.11 use add_path($path)
     */
    public function addLocation(string $path): self
    {
        return $this->add_path($path);
    }

    /**
     * Get stub path.
     */
    public function stubPath(): ?string
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
     * @deprecated 10.0.11 use stubPath()
     */
    public function getStubPath(): ?string
    {
        return $this->stubPath();
    }

    /**
     * Set stub path.
     */
    public function setStubPath(string $stubPath): self
    {
        $this->stubPath = $stubPath;

        return $this;
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
    public function assetPath(string $module): string
    {
        return $this->config('paths.assets') . '/' . $module;
    }

    /**
     * Get module assets path.
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * Get module used for cli session.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function used(): string
    {
        return $this->findOrFail($this->files()->get($this->usedStoragePath()));
    }

    /**
     * @deprecated 10.0.11 use used()
     */
    public function getUsedNow(): string
    {
        return $this->used();
    }

    /**
     * Set module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function use($name)
    {
        $module = $this->findOrFail($name);

        $this->files()->put($this->usedStoragePath(), $module);
    }

    /**
     * @deprecated 10.0.11 use use($name)
     */
    public function setUsed($name)
    {
        $this->use($name);
    }

    /**
     * Get storage path for module used.
     */
    public function usedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->files()->exists($directory) === false) {
            $this->files()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (!$this->files()->exists($path)) {
            $this->files()->put($path, '');
        }

        return $path;
    }

    /**
     * @deprecated 10.0.11 use usedStoragePath()
     */
    public function getUsedStoragePath(): string
    {
        return $this->usedStoragePath();
    }

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed()
    {
        if ($this->files()->exists($this->usedStoragePath())) {
            $this->files()->delete($this->usedStoragePath());
        }
    }
}
