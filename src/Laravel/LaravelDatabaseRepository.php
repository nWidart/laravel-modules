<?php

namespace Nwidart\Modules\Laravel;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\DatabaseRepositoryInterface;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Generators\DatabaseModuleGenerator;
use Nwidart\Modules\Json;
use Nwidart\Modules\Process\Updater;

/**
 * Class LaravelDatabaseRepository.
 *
 * @package Nwidart\Modules\Laravel
 * @method DatabaseModule findOrFail(string $name)
 */
class LaravelDatabaseRepository extends LaravelFileRepository implements DatabaseRepositoryInterface
{
    /**
     * Creates a new Module instance.
     *
     * @param mixed ...$args
     *
     * @return DatabaseModule
     */
    protected function createModule(...$args)
    {
        return new DatabaseModule(...$args);
    }

    /**
     * @return ModuleEntity
     */
    public function getModel(): ModuleEntity
    {
        return new ModuleEntity();
    }

    /**
     * Scan & get all available modules.
     */
    public function scan()
    {
        /**
         * @var ModuleEntity[] $rows
         */
        $rows = $this->getModel()->get();
        $modules = [];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if (file_exists($row->path)) {
                    $module = $this->createModule($this->app, $row->name, $row->path);
                    $module->setAttributes($row->toArray());
                    $modules[$row->name] = $module;
                }
            }
        }

        return $modules;
    }

    /**
     * Get all modules as laravel collection instance.
     *
     * @param $status
     *
     * @return Collection
     */
    public function collections($status = 1): Collection
    {
        return new Collection($this->getByStatus($status));
    }

    /**
     * Get module path for a specific module.
     *
     * @param string $name
     *
     * @return string
     */
    public function getModulePath($name)
    {
        $module = $this->find($name);

        if ($module) {
            return $module->getPath() . DIRECTORY_SEPARATOR;
        }

        return $this->getPath() . DIRECTORY_SEPARATOR . Str::studly($name) . DIRECTORY_SEPARATOR;
    }

    /**
     * Get modules by status.
     *
     * @param $status
     *
     * @return array
     */
    public function getByStatus($status): array
    {
        $modules = [];

        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status) == $status) {
                $modules[$name] = $module;
            }
        }

        return $modules;
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
            $path = $module['path'];
            // Specific case when switching from file to database.
            // BootstrapServiceProvider still boot all modules.
            // Disable boot if module data is not database data (they're cached file data).
            if (!array_key_exists('is_active', $module)) {
                continue;
            }
            $databaseModule = $this->createModule($this->app, $name, $path);
            $databaseModule->setAttributes($module);
            $modules[$name] = $databaseModule;
        }

        return $modules;
    }

    /**
     * Get cached modules from database.
     *
     * @return array
     */
    public function getCached()
    {
        return $this->cache->store($this->config->get('modules.cache.driver'))
            ->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
                return $this->getModel()->all()->keyBy('name')->toArray();
            });
    }

    public function all(): array
    {
        // Do not load or register if there are no modules table yet.
        if (!Schema::hasTable('modules')) {
            return [];
        }

        return parent::all();
    }

    public function create($params, $force = true, $isApi = true, $isPlain = true)
    {
        $moduleType = $this->getModuleType($isApi, $isPlain); // Custom later.
        /** @var DatabaseModuleGenerator $generator */
        $generator = with(new DatabaseModuleGenerator($params['name']));
        $code = $generator
            ->setFilesystem(app('files'))
            ->setModule($this)
            ->setConfig(app('config'))
            ->setActivator(app(ActivatorInterface::class))
            ->setForce($force)
            ->setType($moduleType)
            ->setActive($params['is_active'])
            ->setSilentOutput()
            ->generate();

        return $code ? $this->find($params['name']) : false;
    }

    /**
     * Get module type .
     *
     * @param bool $isApi
     * @param bool $isPlain
     *
     * @return string
     */
    public function getModuleType($isApi = true, $isPlain = true)
    {
        if ($isPlain && $isApi) {
            return 'web';
        }
        if ($isPlain) {
            return 'plain';
        } elseif ($isApi) {
            return 'api';
        } else {
            return 'web';
        }
    }

    /**
     * Get module used for cli session.
     * @return string
     * @throws ModuleNotFoundException|FileNotFoundException
     */
    public function getUsedNow(): string
    {
        $module = $this->getFiles()->get($this->getUsedStoragePath());
        if (!$module) {
            return '';
        }

        return $this->findOrFail($module);
    }

    public function migrateFileToDatabase($forceUpdate = false): array
    {
        $fileRepository = new LaravelFileRepository($this->app);
        $paths = $this->getScanPaths();
        $modules = [];

        // Support modules that already there or copy/paste and not scan yet.
        // So we scan all module.json in every folder.
        foreach ($paths as $path) {
            $manifests = $this->getFiles()->glob($path . DIRECTORY_SEPARATOR . 'module.json');

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $json = Json::make($manifest);
                $data = $json->getAttributes();
                $data['path'] = str_replace('module.json', '', $json->getPath());
                if (!isset($data['version'])) {
                    $data['version'] = $this->config('default_version');
                }
                if (!isset($data['is_active'])) {
                    $data['is_active'] = 1;
                    // Assume this is local module, have to find in module_status.json.
                    if ($fileRepository->find($data['name'])) {
                        $data['is_active'] = $fileRepository->isEnabled($data['name']) === true ? 1 : 0;
                    }
                }
                /** @var DatabaseModule|null $module */
                $module = $this->find($data['name']);
                $data = $this->validateAttributes($data);
                if (!$module) {
                    $modules[] = $this->getModel()->create($data);
                } else {
                    // Check version, if version is higher, then update module.json from file into database.
                    // Can use force update here.
                    if (version_compare($module->getVersion(), $data['version'], '<') || $forceUpdate) {
                        $modules[] = $this->getModel()->where(['name' => $data['name']])->update($data);
                    }
                }
            }
        }

        $this->register();
        $this->boot();

        return $modules;
    }

    public function update($name)
    {
        $module =  $this->findOrFail($name);
        $updater = new Updater($this);
        if ($this->config('database_management.update_file_to_database_when_updating')) {
            $json = Json::make($module->getPath() . DIRECTORY_SEPARATOR . 'module.json');
            $data = $json->getAttributes();

            if (!isset($data['version'])) {
                $data['version'] = $this->config('default_version');
            }

            // Check physical version file, if version is higher than current version
            // then update module.json into database.
            if (version_compare($module->getVersion(), $data['version'], '<=')) {
                $data = $updater->getModule()->validateAttributes($data);
                $this->getModel()->where(['name' => $data['name']])->update($data);
            }
        }

        with($updater)->update($module->getName());
        $module->flushCache();
    }

    /**
     * Validate array attributes before insert/update into database.
     *
     * @param array $attributes
     * @param array $allows
     *
     * @return array
     */
    protected function validateAttributes(array $attributes, array $allows = []): array
    {
        if (empty($allows)) {
            $allows = $this->getModel()->getFillable();
        }

        return array_filter($attributes, function ($k) use ($allows) {
            return in_array($k, $allows);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getOrdered($direction = 'asc'): array
    {
        $modules = $this->allEnabled();

        uasort($modules, function (DatabaseModule $a, DatabaseModule $b) use ($direction) {
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
}
