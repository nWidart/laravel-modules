<?php

namespace Nwidart\Modules\Laravel;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Nwidart\Modules\Collection;
use Illuminate\Container\Container;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Entities\ModuleEntity;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Laravel\Module;

class LaravelEloquentRepository implements RepositoryInterface
{
    /**
     * @var ModuleEntity
     */
    private $moduleEntity;
    /**
     * @var Container
     */
    private $app;

    public function __construct(Container $app, ModuleEntity $moduleEntity)
    {
        $this->app = $app;
        $this->moduleEntity = $moduleEntity;
    }

    /**
     * Get all modules.
     * @return EloquentCollection
     */
    public function all(): array
    {
        return $this->convertToCollection($this->moduleEntity->get())->toArray();
    }

    /**
     * Get cached modules.
     */
    public function getCached(): array
    {
        return $this->app['cache']->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * Scan & get all available modules.
     */
    public function scan(): array
    {
        return $this->toCollection()->toArray();
    }

    /**
     * Get modules as modules collection instance.
     */
    public function toCollection(): Collection
    {
        return $this->convertToCollection($this->moduleEntity->get());
    }

    protected function createModule(...$args)
    {
        return new Module(...$args);
    }

    /**
     * Get scanned paths.
     * @return array
     */
    public function getScanPaths(): array
    {
        return [];
    }

    /**
     * Get list of enabled modules.
     * @return mixed
     */
    public function allEnabled(): array
    {
        $results = $this->moduleEntity->newQuery()->where('is_active', 1)->get();

        return $this->convertToCollection($results)->toArray();
    }

    /**
     * Get list of disabled modules.
     * @return mixed
     */
    public function allDisabled()
    {
        $results = $this->moduleEntity->newQuery()->where('is_active', 0)->get();

        return $this->convertToCollection($results)->toArray();
    }

    /**
     * Get count from all modules.
     * @return int
     */
    public function count(): int
    {
        return $this->moduleEntity->count();
    }

    /**
     * Get all ordered modules.
     */
    public function getOrdered(string $direction = 'asc'): array
    {
        $results = $this->moduleEntity
            ->newQuery()
            ->where('is_active', 1)
            ->orderBy('order', $direction)
            ->get();

        return $this->convertToCollection($results)->toArray();
    }

    /**
     * Get modules by the given status.
     * @param int $status
     * @return array
     */
    public function getByStatus($status): array
    {
        $results = $this->moduleEntity
            ->newQuery()
            ->where('is_active', $status)
            ->get();
        return $this->convertToCollection($results)->toArray();
    }

    /**
     * Find a specific module.
     * @param $name
     * @return \Nwidart\Modules\Contracts\ModuleInterface
     */
    public function find($name): ?\Nwidart\Modules\Contracts\ModuleInterface
    {
        $module = $this->moduleEntity
            ->newQuery()
            ->where('name', $name)
            ->first();

        if ($module === null) {
            return null;
        }

        return $this->createModule($this->app, $module->name, $module->path);
    }

    /**
     * Find a specific module. If there return that, otherwise throw exception.
     * @param $name
     * @return \Nwidart\Modules\Contracts\ModuleInterface
     * @throws ModuleNotFoundException
     */
    public function findOrFail($name): \Nwidart\Modules\Contracts\ModuleInterface
    {
        $module = $this->find($name);

        if ($module === null) {
            throw new ModuleNotFoundException();
        }

        return $module;
    }

    public function getModulePath($moduleName)
    {
        $module = $this->findOrFail($moduleName);

        return $module->getPath();
    }

    /**
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->app['files'];
    }

    public function config($key, $default = null)
    {
        return $this->app['config']->get('modules.' . $key, $default);
    }

    public function exists(string $name): bool
    {
        return (bool) $this->moduleEntity
            ->newQuery()
            ->where('name', $name)
            ->count();
    }

    /**
     * Delete a specific module.
     * @param string $name
     * @return bool
     * @throws \Nwidart\Modules\Exceptions\ModuleNotFoundException
     */
    public function delete($name): bool
    {
        return $this->findOrFail($name)->delete();
    }

    private function convertToCollection(EloquentCollection $eloquentCollection): Collection
    {
        $collection = new Collection();
        $eloquentCollection->map(function ($module) use ($collection) {
            $collection->push($this->createModule($this->app, $module->name, $module->path, $module->toArray()));
        });
        return $collection;
    }

    public function findRequirements($name): array
    {
        // TODO: Implement findRequirements() method.
    }

    public function getPath(): string
    {
        // TODO: Implement getPath() method.
    }

    public function findByAlias(string $alias)
    {
        $module = $this->moduleEntity
            ->newQuery()
            ->where('alias', $alias)
            ->first();

        if ($module === null) {
            return null;
        }

        return $this->createModule($this->app, $module->name, $module->path);
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }

    public function register(): void
    {
        // TODO: Implement register() method.
    }

    public function assetPath(string $module): string
    {
        // TODO: Implement assetPath() method.
    }

    public function isEnabled(string $name): bool
    {
        // TODO: Implement isEnabled() method.
    }

    public function isDisabled(string $name): bool
    {
        // TODO: Implement isDisabled() method.
    }
}
