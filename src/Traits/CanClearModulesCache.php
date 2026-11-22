<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Contracts\RepositoryInterface;

trait CanClearModulesCache
{
    /**
     * Forget the modules the repository has scanned, so the next lookup reads the module.json files again.
     *
     * Only the in-memory scan is reset; the provider manifest in bootstrap/cache/modules.php is kept.
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
