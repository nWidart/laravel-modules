<?php

namespace Nwidart\Modules\Traits;

trait ModuleCommandTrait
{
    public function getModuleName(): string
    {
        $module = $this->argument('module') ?: app('modules')->getUsedNow();

        $module = app('modules')->findOrFail($module);

        return $module->getStudlyName();
    }
}
