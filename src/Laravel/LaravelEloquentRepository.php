<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Entities\ModuleEntity;

class LaravelEloquentRepository implements RepositoryInterface
{
    /**
     * @var ModuleEntity
     */
    private $moduleEntity;

    public function __construct(ModuleEntity $moduleEntity)
    {
        $this->moduleEntity = $moduleEntity;
    }

    /**
     * Get all modules.
     * @return mixed
     */
    public function all()
    {
        return $this->moduleEntity->get();
    }

    /**
     * Get cached modules.
     * @return array
     */
    public function getCached()
    {
        // TODO: Implement getCached() method.
    }

    /**
     * Scan & get all available modules.
     * @return array
     */
    public function scan()
    {
        // TODO: Implement scan() method.
    }

    /**
     * Get modules as modules collection instance.
     * @return \Nwidart\Modules\Collection
     */
    public function toCollection()
    {
        // TODO: Implement toCollection() method.
    }

    /**
     * Get scanned paths.
     * @return array
     */
    public function getScanPaths()
    {
        // TODO: Implement getScanPaths() method.
    }

    /**
     * Get list of enabled modules.
     * @return mixed
     */
    public function allEnabled()
    {
        // TODO: Implement allEnabled() method.
    }

    /**
     * Get list of disabled modules.
     * @return mixed
     */
    public function allDisabled()
    {
        // TODO: Implement allDisabled() method.
    }

    /**
     * Get count from all modules.
     * @return int
     */
    public function count()
    {
        // TODO: Implement count() method.
    }

    /**
     * Get all ordered modules.
     * @param string $direction
     * @return mixed
     */
    public function getOrdered($direction = 'asc')
    {
        // TODO: Implement getOrdered() method.
    }

    /**
     * Get modules by the given status.
     * @param int $status
     * @return mixed
     */
    public function getByStatus($status)
    {
        // TODO: Implement getByStatus() method.
    }

    /**
     * Find a specific module.
     * @param $name
     * @return mixed
     */
    public function find($name)
    {
        // TODO: Implement find() method.
    }

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     * @param $name
     * @return mixed
     */
    public function findOrFail($name)
    {
        // TODO: Implement findOrFail() method.
    }

    public function getModulePath($moduleName)
    {
        // TODO: Implement getModulePath() method.
    }

    /**
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        // TODO: Implement getFiles() method.
    }
}
