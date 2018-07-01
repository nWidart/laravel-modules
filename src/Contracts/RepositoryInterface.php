<?php

namespace Nwidart\Modules\Contracts;

use Nwidart\Modules\Collection;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;

interface RepositoryInterface
{
    /**
     * Get all modules
     */
    public function all(): array;

    /**
     * Get cached modules
     */
    public function getCached(): array;

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
     *
     * @return array
     */
    public function allEnabled(): array;

    /**
     * Get list of disabled modules.
     *
     * @return mixed
     */
    public function allDisabled();

    /**
     * Get count from all modules.
     */
    public function count(): int;

    /**
     * Get all ordered modules.
     */
    public function getOrdered(string $direction = 'asc'): array;

    /**
     * Get modules by the given status.
     *
     * @param int $status
     *
     * @return array
     */
    public function getByStatus($status): array;

    /**
     * Find a specific module.
     *
     * @param $name
     *
     * @return mixed
     */
    public function find($name);

    /**
     * Find a specific module.
     * @param $name
     * @return mixed
     * @throws ModuleNotFoundException
     */
    public function findOrFail($name);

    public function getModulePath($moduleName);

    /**
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles();
}
