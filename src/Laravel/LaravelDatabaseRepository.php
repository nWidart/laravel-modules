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
 * Class LaravelDatabaseRepository
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
    public function getModel()
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
                    $modules[$row->name] = $this->createModule($this->app, $row->name, $row->path);
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
     * @param $name
     *
     * @return string
     */
    public function getModulePath($name)
    {
        $module = $this->find($name);
        if ($module) {
            return $module->getPath() . '/';
        }

        return $this->getPath() . '/' . Str::studly($name) . '/';
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

        foreach ($cached as $moduleEntity) {
            $module = $this->createModule($this->app, $moduleEntity['name'], $moduleEntity['path']);
            $module->setAttributes($moduleEntity->toArray());
            $modules[$moduleEntity['name']] = $module;
        }

        return $modules;
    }

    /**
     * Get cached modules from database.
     *
     * @return ModuleEntity[]
     */
    public function getCached()
    {
        return $this->app['cache']->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->getModel()->all();
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
            ->setSilentOutput(true) // Don't use console output
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

    public function migrateFileToDatabase()
    {
        $paths = $this->getScanPaths();
        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = $this->getFiles()->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $json = Json::make($manifest);
                $data = $json->getAttributes();
                $data['path'] = str_replace('module.json', '', $json->getPath());
                if (!isset($data['version'])) {
                    $data['version'] = '1.0.0';
                }
                $module = $this->find($data['name']);
                $data = $this->validateAttributes($data);
                if (!$module) {
                    $modules[] = $this->getModel()->create($data);
                } else {
                    // Check version, if version is higher then update module.json into database.
                    if (version_compare($module->getVersion(), $data['version'], '<')) {
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
        return $this->findOrFail($name)->update(new Updater($this));
    }

    /**
     * Validate array attributes before insert/update into database.
     *
     * @param array $attributes
     * @param array $allows
     *
     * @return array
     */
    protected function validateAttributes(array $attributes, array $allows = [])
    {
        if (empty($allows)) {
            $allows = $this->getModel()->getFillable();
        }

        return array_filter($attributes, function ($k) use ($allows) {
            return in_array($k, $allows);
        }, ARRAY_FILTER_USE_KEY);
    }
}
