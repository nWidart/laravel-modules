<?php

namespace Nwidart\Modules\Contracts;

use Nwidart\Modules\Module;

interface ActivatorInterface
{
    /**
     * Enables a module
     *
     * @param Module $module
     */
    public function enable(Module $module);

    /**
     * Disables a module
     *
     * @param Module $module
     */
    public function disable(Module $module);

    /**
     * Determine whether the given status same with a module status.
     *
     * @param Module $module
     * @param $status
     *
     * @return bool
     */
    public function isStatus(Module $module, $status);

    /**
     * Set active state for a module.
     *
     * @param Module $module
     * @param $active
     */
    public function setActive(Module $module, $active);

    /**
     * Sets a module status by its name
     * 
     * @param  string $name
     * @param  $active
     */
    public function setActiveByName(string $name, $active);

    /**
     * Deletes a module activation status
     * 
     * @param  Module $module
     */
    public function delete(Module $module);
}
