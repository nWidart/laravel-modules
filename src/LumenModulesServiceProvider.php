<?php

namespace Nwidart\Modules;

use Nwidart\Modules\Support\Stub;

class LumenModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        $this->registerNamespaces();
        $this->registerModules();
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath()
    {
        Stub::setBasePath(__DIR__.'/Commands/stubs');

        if (app('modules')->config('stubs.enabled') === true) {
            Stub::setBasePath(app('modules')->config('stubs.path'));
        }
    }
}