<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    public function register()
    {
        $this->app->bind(
            'Nwidart\Modules\Contracts\RepositoryInterface',
            'Nwidart\Modules\Repository'
        );
    }
}
