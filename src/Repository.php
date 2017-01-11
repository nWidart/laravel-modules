<?php

namespace Nwidart\Modules;

use Countable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Process\Updater;

class Repository implements RepositoryInterface, Countable
{
    use Macroable;

    /**
     * Application instance.
     *
     * @var Application
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
     * The constructor.
     *
     * @param Application $app
     * @param string|null $path
     */
    public function __construct(Application $app, $path = null)
    {
        $this->app = $app;
        $this->path = $path;
    }

    /**
     * Add other module location.
     *
     * @param string $path
     *
     * @return $this
     */
    public function addLocation($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Alternative method for "addPath".
     *
     * @param string $path
     *
     * @return $this
     */
    public function addPath($path)
    {
        return $this->addLocation($path);
    }

    /**
     * Get all additional paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Get scanned modules paths.
     *
     * @return array
     */
    public function getScanPaths()
    {
        $paths = $this->paths;

        $paths[] = $this->getPath() . '/*';

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        return $paths;
    }

    /**
     * Get & scan all modules.
     *
     * @return array
     */
    public function scan()
    {
        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = $this->app['files']->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = Json::make($manifest)->get('name');

                $modules[$name] = new Module($this->app, $name, dirname($manifest));
            }
        }

        return $modules;
    }

    /**
     * Get all modules.
     *
     * @return array
     */
    public function all()
    {
        if (!$this->config('cache.enabled')) {
            return $this->scan();
        }

        return $this->formatCached($this->getCached());
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
            $path = $this->config('paths.modules') . '/' . $name;

            $modules[$name] = new Module($this->app, $name, $path);
        }

        return $modules;
    }

    /**
     * Get cached modules.
     *
     * @return array
     */
    public function getCached()
    {
        return $this->app['cache']->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection
     */
    public function toCollection()
    {
        return new Collection($this->scan());
    }

    /**
     * Get modules by status.
     *
     * @param $status
     *
     * @return array
     */
    public function getByStatus($status)
    {
        $modules = [];

        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status)) {
                $modules[$name] = $module;
            }
        }

        return $modules;
    }

    /**
     * Determine whether the given module exist.
     *
     * @param $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * Get list of enabled modules.
     *
     * @return array
     */
    public function enabled()
    {
        return $this->getByStatus(1);
    }

    /**
     * Get list of disabled modules.
     *
     * @return array
     */
    public function disabled()
    {
        return $this->getByStatus(0);
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
     *
     * @return array
     */
    public function getOrdered($direction = 'asc')
    {
        $modules = $this->enabled();

        uasort($modules, function (Module $a, Module $b) use ($direction) {
            if ($a->order == $b->order) {
                return 0;
            }

            if ($direction == 'desc') {
                return $a->order < $b->order ? 1 : -1;
            }

            return $a->order > $b->order ? 1 : -1;
        });

        return $modules;
    }

    /**
     * Get a module path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path ?: $this->config('paths.modules');
    }

    /**
     * Register the modules.
     */
    public function register()
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * Boot the modules.
     */
    public function boot()
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * Find a specific module.
     * @param $name
     * @return mixed|void
     */
    public function find($name)
    {
        foreach ($this->all() as $module) {
            if ($module->getLowerName() === strtolower($name)) {
                return $module;
            }
        }

        return;
    }

    /**
     * Find a specific module by its alias.
     * @param $alias
     * @return mixed|void
     */
    public function findByAlias($alias)
    {
        foreach ($this->all() as $module) {
            if ($module->getAlias() === $alias) {
                return $module;
            }
        }

        return;
    }

    /**
     * Find all modules that are required by a module. If the module cannot be found, throw an exception.
     *
     * @param $name
     * @return array
     * @throws ModuleNotFoundException
     */
    public function findRequirements($name)
    {
        $requirements = [];

        $module = $this->findOrFail($name);

        foreach ($module->getRequires() as $requirementName) {
            $requirements[] = $this->findByAlias($requirementName);
        }

        return $requirements;
    }

    /**
     * Alternative for "find" method.
     * @param $name
     * @return mixed|void
     */
    public function get($name)
    {
        return $this->find($name);
    }

    /**
     * Find a specific module, if there return that, otherwise throw exception.
     *
     * @param $name
     *
     * @return Module
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail($name)
    {
        $module = $this->find($name);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$name}] does not exist!");
    }

    /**
     * Get all modules as laravel collection instance.
     *
     * @return Collection
     */
    public function collections()
    {
        return new Collection($this->enabled());
    }

    /**
     * Get module path for a specific module.
     *
     * @param $module
     *
     * @return string
     */
    public function getModulePath($module)
    {
        try {
            return $this->findOrFail($module)->getPath() . '/';
        } catch (ModuleNotFoundException $e) {
            return $this->getPath() . '/' . Str::studly($module) . '/';
        }
    }

    /**
     * Get asset path for a specific module.
     *
     * @param $module
     *
     * @return string
     */
    public function assetPath($module)
    {
        return $this->config('paths.assets') . '/' . $module;
    }

    /**
     * Get a specific config data from a configuration file.
     *
     * @param $key
     *
     * @param null $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return $this->app['config']->get('modules.' . $key, $default);
    }

    /**
     * Get storage path for module used.
     *
     * @return string
     */
    public function getUsedStoragePath()
    {
        if (!$this->app['files']->exists($path = storage_path('app/modules'))) {
            $this->app['files']->makeDirectory($path, 0777, true);
        }

        return $path . '/modules.used';
    }

    /**
     * Set module used for cli session.
     *
     * @param $name
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed($name)
    {
        $module = $this->findOrFail($name);

        $this->app['files']->put($this->getUsedStoragePath(), $module);
    }

    /**
     * Get module used for cli session.
     *
     * @return string
     */
    public function getUsedNow()
    {
        return $this->findOrFail($this->app['files']->get($this->getUsedStoragePath()));
    }

    /**
     * Get used now.
     *
     * @return string
     */
    public function getUsed()
    {
        return $this->getUsedNow();
    }

    /**
     * Get laravel filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->app['files'];
    }

    /**
     * Get module assets path.
     *
     * @return string
     */
    public function getAssetsPath()
    {
        return $this->config('paths.assets');
    }

    /**
     * Get asset url from a specific module.
     *
     * @param string $asset
     *
     * @return string
     */
    public function asset($asset)
    {
        list($name, $url) = explode(':', $asset);

        $baseUrl = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->app['url']->asset($baseUrl . "/{$name}/" . $url);

        return str_replace(['http://', 'https://'], '//', $url);
    }

    /**
     * Determine whether the given module is activated.
     *
     * @param string $name
     *
     * @return bool
     */
    public function active($name)
    {
        return $this->findOrFail($name)->active();
    }

    /**
     * Determine whether the given module is not activated.
     *
     * @param string $name
     *
     * @return bool
     */
    public function notActive($name)
    {
        return !$this->active($name);
    }

    /**
     * Enabling a specific module.
     *
     * @param string $name
     *
     * @return bool
     */
    public function enable($name)
    {
        return $this->findOrFail($name)->enable();
    }

    /**
     * Disabling a specific module.
     *
     * @param string $name
     *
     * @return bool
     */
    public function disable($name)
    {
        return $this->findOrFail($name)->disable();
    }

    /**
     * Delete a specific module.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete($name)
    {
        return $this->findOrFail($name)->delete();
    }

    /**
     * Update dependencies for the specified module.
     *
     * @param string $module
     */
    public function update($module)
    {
        with(new Updater($this))->update($module);
    }

    /**
     * Install the specified module.
     *
     * @param string $name
     * @param string $version
     * @param string $type
     * @param bool   $subtree
     *
     * @return \Symfony\Component\Process\Process
     */
    public function install($name, $version = 'dev-master', $type = 'composer', $subtree = false)
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }

    /**
     * Get stub path.
     *
     * @return string
     */
    public function getStubPath()
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
     *
     * @param string $stubPath
     *
     * @return $this
     */
    public function setStubPath($stubPath)
    {
        $this->stubPath = $stubPath;

        return $this;
    }
}
