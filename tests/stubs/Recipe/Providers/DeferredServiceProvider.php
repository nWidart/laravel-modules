<?php

namespace Modules\Recipe\Providers;

use Illuminate\Support\ServiceProvider;

class DeferredServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        app()->bind('foo', function () {
            return 'bar';
        });

        app()->bind('deferred', function () {
            return;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['deferred'];
    }
}
