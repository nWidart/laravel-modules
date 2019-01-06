<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Module;

trait ModuleCommandTrait
{
    /**
     * Get the module name.
     *
     * @return string
     */
    public function getModuleName()
    {
        $module = $this->getModule();

        return $module->getStudlyName();
    }

    /**
     * Get the module by argument or used now
     *
     * @return mixed|Module
     */
    public function getModule()
    {
        $moduleName = $this->argument('module') ?: app('modules')->getUsedNow();

        return app('modules')->findOrFail($moduleName);
    }
}
