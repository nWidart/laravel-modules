<?php

use Illuminate\Foundation\Vite;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;

if (! function_exists('module')) {
    /**
     * Retrieves a module status or its instance.
     *
     * @param  string  $name  The name of the module.
     * @param  bool  $instance  Whether to return the module's instance instead of the status. Defaults to false [status].
     * @return bool|Module The module instance or its status.
     */
    function module(string $name, bool $instance = false): bool|Module
    {
        /** @var RepositoryInterface $repository */
        $repository = app('modules');

        try {
            $module = $repository->findOrFail($name);

            return $instance ? $module : $module->isEnabled();
        } catch (ModuleNotFoundException $exception) {
            return false;
        }
    }
}

if (! function_exists('module_path')) {
    /**
     * Get the path of a module, or of a file inside it.
     *
     * @throws ModuleNotFoundException
     */
    function module_path(string $name, string $path = ''): string
    {
        /** @var RepositoryInterface $repository */
        $repository = app('modules');

        $module = $repository->findOrFail($name);

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
     * Get a Vite instance configured for a module's build.
     *
     * Configures a clone so the shared Vite instance used by `@vite` stays untouched.
     */
    function module_vite(string $module, string $asset, ?string $hotFilePath = null): Vite
    {
        return (clone app(Vite::class))->useHotFile($hotFilePath ?: storage_path('vite.hot'))->useBuildDirectory($module)->withEntryPoints([$asset]);
    }
}
