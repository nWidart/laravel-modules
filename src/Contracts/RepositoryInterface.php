<?php

namespace Nwidart\Modules\Contracts;

use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;

interface RepositoryInterface
{
    /**
     * Get all modules.
     *
     * @return mixed
     */
    public function all();

    /**
     * Get cached modules.
     *
     * @return array
     */
    public function getCached();

    /**
     * Scan & get all available modules.
     *
     * @return array
     */
    public function scan();

    /**
     * Get modules as modules collection instance.
     *
     * @return \Nwidart\Modules\Collection
     */
    public function toCollection();

    /**
     * Get scanned paths.
     *
     * @return array
     */
    public function getScanPaths();

    /**
     * Get list of enabled modules.
     *
     * @return mixed
     */
    public function allEnabled();

    /**
     * Get list of disabled modules.
     *
     * @return mixed
     */
    public function allDisabled();

    /**
     * Get count from all modules.
     *
     * @return int
     */
    public function count();

    /**
     * Get all ordered modules.
     * @param string $direction
     * @return mixed
     */
    public function getOrdered($direction = 'asc');

    /**
     * Get modules by the given status.
     *
     * @param int $status
     *
     * @return mixed
     */
    public function getByStatus($status);

    /**
     * Find a specific module.
     *
     * @param $name
     * @return Module|null
     */
    public function find(string $name);

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @param $name
     *
     * @return mixed
     */
    public function findOrFail(string $name);

    public function getModulePath($moduleName);

    /**
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles();

    /**
     * Get a specific config data from a configuration file.
     * @param string $key
     *
     * @param string|null $default
     * @return mixed
     */
    public function config(string $key, $default = null);

    /**
     * Get a module path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Boot the modules.
     */
    public function boot(): void;

    /**
     * Register the modules.
     */
    public function register(): void;

    /**
     * Get asset path for a specific module.
     *
     * @param string $module
     * @return string
     */
    public function assetPath(string $module): string;

    /**
     * Delete a specific module.
     * @param string $module
     * @return bool
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function delete(string $module): bool;

    /**
     * Determine whether the given module is activated.
     * @param string $name
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $name): bool;

    /**
     * Determine whether the given module is not activated.
     * @param string $name
     * @return bool
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $name): bool;
}
