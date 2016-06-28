<?php

namespace Nwidart\Modules\Traits;

trait ModuleCommandTrait
{
    /**
     * Get the module name.
     *
     * @return string
     */
    public function getModuleName()
    {
        $module = $this->argument('module') ?: $this->laravel['modules']->getUsedNow();

        $module = $this->laravel['modules']->findOrFail($module);

        return $module->getStudlyName();
    }
}
