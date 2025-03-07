<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Vite as ViteFacade;

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
