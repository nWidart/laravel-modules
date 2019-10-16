<?php

namespace Nwidart\Modules;

use Countable;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Traits\Macroable;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Entities\Module as ModuleEntity;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;

abstract class DatabaseRepository implements RepositoryInterface, Countable
{
    use Macroable;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
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
     *
     * @param Container   $app
     * @param string|null $path
     */
    public function __construct(Container $app, $path = null)
    {
        $this->app = $app;
        $this->path = $path;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->files = $app['files'];
        $this->cache = $app['cache'];
    }

    /**
     * Get all modules.
     *
     * @return mixed
     */
    public function all()
    {
        if (!$this->config('cache.enabled')) {
            return $this->scan();
        }

        return $this->formatCached($this->getCached());
    }

    /**
     * Get cached modules.
     *
     * @return array
     */
    public function getCached()
    {
        return $this->cache->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * Scan & get all available modules.
     *
     * @return array
     */
    public function scan()
    {
        $entities = ModuleEntity::all();

        return $this->parse($entities);
    }

    /**
     * Get modules as modules collection instance.
     *
     * @return \Nwidart\Modules\Collection
     */
    public function toCollection()
    {
        return new Collection($this->scan());
    }

    /**
     * Get scanned paths.
     *
     * @return array
     */
    public function getScanPaths()
    {
        // TODO: Unused thus remove
    }

    /**
     * Determine whether the given module exist.
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name) : bool
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * Get list of enabled modules.
     *
     * @return mixed
     */
    public function allEnabled()
    {
        return $this->getByStatus(true);
    }

    /**
     * Get list of disabled modules.
     *
     * @return mixed
     */
    public function allDisabled()
    {
        return $this->getByStatus(false);
    }

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * Get all ordered modules.
     *
     * @param string $direction
     * @return mixed
     */
    public function getOrdered($direction = 'asc')
    {
        $entities = ModuleEntity::orderBy('sequence', $direction)->get();

        return $this->parse($entities);
    }

    /**
     * Get modules by the given status.
     *
     * @param int $status
     *
     * @return mixed
     */
    public function getByStatus($status)
    {
        $entities = ModuleEntity::where('is_active', $status)->get();

        return $this->parse($entities);
    }

    /**
     * Find a specific module.
     *
     * @param $name
     * @return Module|null
     */
    public function find(string $name)
    {
        $entity = ModuleEntity::findByName($name);

        if (!$entity) {
            return null;
        }

        return $this->createModule($this->app, $entity->name, $entity->path);
    }

    /**
     * Find all modules that are required by a module. If the module cannot be found, throw an exception.
     *
     * @param $name
     * @return array
     * @throws ModuleNotFoundException
     */
    public function findRequirements($name): array
    {
        $requirements = [];

        $module = $this->findOrFail($name);

        foreach ($module->getRequires() as $requirementName) {
            $requirements[] = $this->findByAlias($requirementName);
        }

        return $requirements;
    }

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @param $name
     *
     * @return mixed
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function findOrFail(string $name)
    {
        $module = $this->find($name);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$name}] does not exist!");
    }

    /**
     * Find a specific module. If there return that, otherwise create it.
     *
     * @param $name
     *
     * @return Module
     */
    public function findByNameOrCreate(string $name)
    {
        $module = $this->find($name);

        return $this->createModule($this->app, $name, $this->getModulePath($name));
    }

    /**
     * @param $name
     * @return string
     */
    public function getModulePath($name)
    {
        return $this->getPath() . '/' . $name;
    }

    /**
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get a specific config data from a configuration file.
     *
     * @param string      $key
     *
     * @param string|null $default
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return $this->config->get('modules.' . $key, $default);
    }

    /**
     * Get a module path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path($this->config->get('paths.modules')));
    }

    /**
     * Find a specific module by its alias.
     *
     * @param string $alias
     * @return Module|void
     */
    public function findByAlias(string $alias)
    {
        foreach ($this->all() as $module) {
            if ($module->getAlias() === $alias) {
                return $module;
            }
        }

        return;
    }

    /**
     * Boot the modules.
     */
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * Register the modules.
     */
    public function register(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * Get asset path for a specific module.
     *
     * @param string $module
     * @return string
     */
    public function assetPath(string $module): string
    {
        return $this->config('paths.assets') . '/' . $module;
    }

    /**
     * Delete a specific module.
     *
     * @param string $module
     * @return bool
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function delete(string $module): bool
    {
        return $this->findOrFail($module)->delete();
    }

    /**
     * Determine whether the given module is activated.
     *
     * @param string $name
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $name): bool
    {
        return $this->findOrFail($name)->isEnabled();
    }

    /**
     * Determine whether the given module is not activated.
     *
     * @param string $name
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $name): bool
    {
        return !$this->isEnabled($name);
    }

    /**
     * Format the cached data as array of modules.
     *
     * @param array $cached
     *
     * @return array
     */
    protected function formatCached($cached)
    {
        $modules = [];

        foreach ($cached as $name => $module) {
            $modules[$name] = $this->createModule($this->app, $name, $this->getModulePath($name));
        }

        return $modules;
    }

    /**
     * Creates a new Module instance
     *
     * @param Container $app
     * @param string $args
     * @param string $path
     * @return \Nwidart\Modules\Module
     */
    abstract protected function createModule(...$args);

    /**
     * Parse from ModuleEntityCollection to Module array
     *
     * @param \Illuminate\Database\Eloquent\Collection $entities
     * @return array
     */
    protected function parse(\Illuminate\Database\Eloquent\Collection $entities)
    {
        $modules = [];

        foreach ($entities as $entity) {
            $modules[$entity->name] = $this->createModule($this->app, $entity->name, $entity->path);
        }

        return $modules;
    }
}
