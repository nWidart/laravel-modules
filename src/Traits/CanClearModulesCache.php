<?php

namespace Nwidart\Modules\Traits;

trait CanClearModulesCache
{
    /**
     * Clear the modules cache if it is enabled
     * @param bool $force
     */
    public function clearCache($force = false)
    {
        if ($force === true || config('modules.cache.enabled') === true) {
            app('cache')->forget(config('modules.cache.key'));
        }
    }
}
