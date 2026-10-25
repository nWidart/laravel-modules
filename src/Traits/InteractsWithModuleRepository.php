<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Contracts\RepositoryInterface;

/**
 * Typed access to the module repository for artisan commands.
 */
trait InteractsWithModuleRepository
{
    /**
     * Get the module repository.
     *
     * Resolved by its 'modules' alias, like the Module facade: an app or test that swaps the
     * facade (Module::swap(), Module::shouldReceive()) replaces 'modules' only, not RepositoryInterface.
     *
     * @return RepositoryInterface
     */
    protected function moduleRepository()
    {
        return $this->laravel->make('modules');
    }
}
