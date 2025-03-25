<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Nwidart\Modules\Laravel\Module;

if (! function_exists('module')) {
    /**
     * Retrieves a module instance or its status.
     *
     * @param  string  $name  The name of the module.
     * @param  bool  $status  Whether to return the module's status instead of the instance. Defaults to false.
     * @return Module|bool The module instance or its status.
     */
    function module(string $name, bool $status = false): Module|bool
    {
        $modules = app('modules');
        if (! $modules->has($name)) {
            Log::error("Module '$name' not found.");

            return false;
        }

        return $status ? $modules->isEnabled($name) : $modules->find($name);
    }
}

if (! function_exists('module_path')) {
    function module_path(string $name, string $path = ''): string
    {
        $module = app('modules')->find($name);

        return $module->getPath().($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     */
    function config_path(string $path = ''): string
    {
        return app()->basePath().'/config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     */
    function public_path(string $path = ''): string
    {
        return app()->make('path.public').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path);
    }
}

if (! function_exists('module_vite')) {
    /**
     * support for vite
     */
    function module_vite(string $module, string $asset, ?string $hotFilePath = null): Vite
    {
        return ViteFacade::useHotFile($hotFilePath ?: storage_path('vite.hot'))->useBuildDirectory($module)->withEntryPoints([$asset]);
    }
}
