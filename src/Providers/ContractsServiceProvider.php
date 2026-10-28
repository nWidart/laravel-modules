<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * @deprecated No longer registered and binds nothing. The module repository is
 *             registered as a singleton by the package service provider.
 *             Kept for backward compatibility and will be removed in a future major version.
 */
class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     *
     * The repository used to be bound here with a non-shared bind(), which replaced
     * the singleton registered by the package service provider (issue #2192).
     */
    #[\Override]
    public function register() {}
}
