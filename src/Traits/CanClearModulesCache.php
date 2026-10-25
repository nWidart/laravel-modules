<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Contracts\RepositoryInterface;

trait CanClearModulesCache
{
    /**
     * Clear the modules cache if it is enabled
     *
     * @return void
     */
    public function clearCache()
    {
        /** @var RepositoryInterface $modules */
        $modules = $this->laravel['modules'];

        $modules->resetModules();
    }
}
