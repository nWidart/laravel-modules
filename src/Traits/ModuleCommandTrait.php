<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Contracts\RepositoryInterface;

trait ModuleCommandTrait
{
    public function getModuleName(): string
    {
        /** @var RepositoryInterface $modules */
        $modules = app('modules');

        $module = $this->argument('module') ?: $modules->getUsedNow();

        $module = $modules->findOrFail($module);

        return $module->getStudlyName();
    }
}
