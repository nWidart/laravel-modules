<?php

namespace Nwidart\Modules\Activators;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Entities\Module as ModuleEntity;
use Nwidart\Modules\Module;

class DatabaseActivator implements ActivatorInterface
{
    /**
     * Laravel cache instance
     *
     * @var CacheManager
     */
    private $cache;

    /**
     * Laravel Filesystem instance
     *
     * @var Filesystem
     */
    private $files;

    /**
     * Laravel config instance
     *
     * @var Config
     */
    private $config;

    /**
     * DatabaseActivator constructor.
     *
     * @param \Illuminate\Container\Container $app
     */
    public function __construct(Container $app)
    {
        $this->cache = $app['cache'];
        $this->files = $app['files'];
        $this->config = $app['config'];
    }

    /**
     * Enables a module
     *
     * @param Module $module
     */
    public function enable(Module $module): void
    {
        $this->setActive($module, true);
    }

    /**
     * Disables a module
     *
     * @param Module $module
     */
    public function disable(Module $module): void
    {
        $this->setActive($module, false);
    }

    /**
     * Determine whether the given status same with a module status.
     *
     * @param Module $module
     * @param bool   $status
     *
     * @return bool
     */
    public function hasStatus(Module $module, bool $status): bool
    {
        $entity = ModuleEntity::findByName($module->getName());

        return $entity ? $entity->hasStatus($status) : false;
    }

    /**
     * Set active state for a module.
     *
     * @param Module $module
     * @param bool   $active
     */
    public function setActive(Module $module, bool $active): void
    {
        /** @var ModuleEntity $entity */
        $entity = ModuleEntity::findByNameOrCreate($module->getName(), $module->getPath());

        $entity->setActive($active);
    }

    /**
     * Sets a module status by its name
     *
     * @param string $name
     * @param bool   $active
     */
    public function setActiveByName(string $name, bool $active): void
    {
        $entity = ModuleEntity::findByNameOrCreate($name);

        $entity->setActive($active);
    }

    /**
     * Deletes a module activation status
     *
     * @param Module $module
     * @throws \Exception
     */
    public function delete(Module $module): void
    {
        $entity = ModuleEntity::findByNameOrFail($module->getName());

        $entity->delete();
    }

    /**
     * Deletes any module activation statuses created by this class.
     */
    public function reset(): void
    {
        ModuleEntity::deleteAll();
    }
}
