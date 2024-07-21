<?php

namespace Nwidart\Modules\Contracts;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;

interface RepositoryInterface
{
    /**
     * Boot the modules.
     */
    public function boot(): void;

    /**
     * Register the modules.
     */
    public function register(): void;

    /**
     * Get all modules.
     */
    public function all(): array;

    /**
     * @deprecated 10.0.11 use all(true) or status(true)
     */
    public function allEnabled(): array;

    /**
     * @deprecated 10.0.11 use all(false) or status(false)
     */
    public function allDisabled(): array;

    /**
     * Find a specific module.
     */
    public function find(string $name): ?Module;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     */
    public function findOrFail(string $name): Module;

    /**
     * Get modules by active status [true|false].
     */
    public function status(bool $status): array;

    /**
     * @deprecated 10.0.11 use status()
     */
    public function getByStatus(int $status): array;

    /**
     * Scan & get all available modules.
     */
    public function scan(): array;

    /**
     * Get scanned modules paths.
     */
    public function scanPaths(): array;

    /**
     * @deprecated 10.0.11 use scanPaths()
     */
    public function getScanPaths(): array;

    /**
     * Get cached modules.
     */
    public function cached(): array;

    /**
     * @deprecated 10.0.11 use cached()
     */
    public function getCached(): array;

    /**
     * Get all modules as collection instance.
     */
    public function toCollection(): Collection;

    /**
     * Get all ordered modules.
     */
    public function ordered(string $sort = 'asc'): array;

    /**
     * @deprecated 10.0.11 use ordered()
     */
    public function getOrdered(string $direction = 'asc'): array;

    /**
     * Delete a specific module.
     *
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function delete(string $module): bool;

    /**
     * Get a specific config data from a configuration file.
     */
    public function config(string $key, ?string $default = null): mixed;

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Determine whether the given module exist.
     */
    public function has(string $name): bool;

    /**
     * Determine if the given module is enabled.
     *
     * @throws ModuleNotFoundException
     */
    public function enabled(string $name): bool;

    /**
     * @deprecated 10.0.11 use enabled()
     */
    public function isEnabled(string $name): bool;

    /**
     * Determine if the given module is disabled.
     *
     * @throws ModuleNotFoundException
     */
    public function disabled(string $name): bool;

    /**
     * @deprecated 10.0.11 use disabled()
     */
    public function isDisabled(string $name): bool;

    /**
     * Get laravel filesystem instance.
     */
    public function files(): Filesystem;

    /**
     * @deprecated 10.0.11 use files()
     */
    public function getFiles(): Filesystem;

    /**
     * Get a module path.
     */
    public function path(): string;

    /**
     * @deprecated 10.0.11 use path()
     */
    public function getPath(): string;

    /**
     * Get module path for a specific module.
     */
    public function modulePath($moduleName): string;

    /**
     * @deprecated 10.0.11 use modulePath()
     */
    public function getModulePath($moduleName): string;

    /**
     * Get asset path for a specific module.
     */
    public function assetPath(string $module): string;
}
