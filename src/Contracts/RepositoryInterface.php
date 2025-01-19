<?php

namespace Nwidart\Modules\Contracts;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;

interface RepositoryInterface
{
    /**
     * Get all modules.
     */
    public function all();

    /**
     * Scan & get all available modules.
     */
    public function scan(): array;

    /**
     * Get modules as modules collection instance.
     */
    public function toCollection(): Collection;

    /**
     * Get scanned paths.
     */
    public function getScanPaths(): array;

    /**
     * Get list of enabled modules.
     */
    public function allEnabled();

    /**
     * Get list of disabled modules.
     */
    public function allDisabled();

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Get all ordered modules.
     */
    public function getOrdered(string $direction = 'asc');

    /**
     * Get modules by the given status.
     */
    public function getByStatus(int|bool $status);

    /**
     * Find a specific module.
     */
    public function find(string $name): ?Module;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     */
    public function findOrFail(string $name);

    public function getModulePath(string $moduleName);

    /**
     * Get Files
     */
    public function getFiles(): Filesystem;

    /**
     * Get a specific config data from a configuration file.
     */
    public function config(string $key, $default = null);

    /**
     * Get a module path.
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
     */
    public function assetPath(string $module): string;

    /**
     * Delete a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function delete(string $module): bool;

    /**
     * Determine whether the given module is activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $name): bool;

    /**
     * Determine whether the given module is not activated.
     *
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $name): bool;
}
