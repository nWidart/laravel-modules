<?php

namespace Nwidart\Modules\Contracts;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Collection;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;

/**
 * The package commands also call these methods on the bound repository. They are not declared
 * as interface methods, so existing custom implementations stay compatible; FileRepository
 * implements them.
 *
 * @method void setUsed(string $name) Set the module used for the cli session.
 * @method void forgetUsed(Module|null $module = null) Forget the module used for the cli session. module:unuse passes the module; FileRepository ignores it.
 * @method string getUsedNow() Get the module used for the cli session.
 * @method void update(string $module) Update the dependencies of the given module.
 * @method $this resetModules() Clear the cached scan of the modules.
 */
interface RepositoryInterface
{
    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    public function all();

    /**
     * Scan & get all available modules.
     *
     * @return array<string, Module>
     */
    public function scan(): array;

    /**
     * Get modules as modules collection instance.
     *
     * @return Collection<string, Module>
     */
    public function toCollection(): Collection;

    /**
     * Get scanned paths.
     *
     * @return array<int, string>
     */
    public function getScanPaths(): array;

    /**
     * Get list of enabled modules.
     *
     * @return array<string, Module>
     */
    public function allEnabled();

    /**
     * Get list of disabled modules.
     *
     * @return array<string, Module>
     */
    public function allDisabled();

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Get all ordered modules.
     *
     * @return array<string, Module>
     */
    public function getOrdered(string $direction = 'asc');

    /**
     * Get modules by the given status.
     *
     * @return array<string, Module>
     */
    public function getByStatus(int|bool $status);

    /**
     * Find a specific module.
     */
    public function find(string $name): ?Module;

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     *
     * @return Module
     *
     * @throws ModuleNotFoundException
     */
    public function findOrFail(string $name);

    /**
     * Get the path of a module. A module that does not exist gets the path it would have.
     *
     * @return string
     */
    public function getModulePath(string $moduleName);

    /**
     * Get Files
     */
    public function getFiles(): Filesystem;

    /**
     * Get a specific config data from a configuration file.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function config(string $key, $default = null);

    /**
     * Get a module path.
     */
    public function getPath(): string;

    /**
     * Boot the modules.
     *
     * @deprecated The package no longer calls this method: enabled modules are loaded through
     *             ModuleManifest, and each module boots through its own service providers.
     *             Will be removed in v14.
     */
    public function boot(): void;

    /**
     * Register the modules.
     *
     * @deprecated The package no longer calls this method: enabled modules are registered by the
     *             package service provider through ModuleManifest. Will be removed in v14.
     */
    public function register(): void;

    /**
     * Get asset path for a specific module.
     */
    public function assetPath(string $module): string;

    /**
     * Delete a specific module.
     *
     * @throws ModuleNotFoundException
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
