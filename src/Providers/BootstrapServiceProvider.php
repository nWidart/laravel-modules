<?php

namespace Nwidart\Modules\Providers;

use Illuminate\Support\ServiceProvider;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot()
    {
        $this->app['modules']->boot();
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->app['modules']->register();
    }
}
