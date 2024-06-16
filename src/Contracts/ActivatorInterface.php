<?php

namespace Nwidart\Modules\Contracts;

use Nwidart\Modules\Module;

interface ActivatorInterface
{
    /**
     * Enables a module
     */
    public function enable(Module $module): void;

    /**
     * Disables a module
     */
    public function disable(Module $module): void;

    /**
     * Determine whether the given status same with a module status.
     */
    public function hasStatus(Module $module, bool $status): bool;

    /**
     * Set active state for a module.
     */
    public function setActive(Module $module, bool $active): void;

    /**
     * Sets a module status by its name
     */
    public function setActiveByName(string $name, bool $active): void;

    /**
     * Deletes a module activation status
     */
    public function delete(Module $module): void;

    /**
     * Deletes any module activation statuses created by this class.
     */
    public function reset(): void;
}
