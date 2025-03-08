<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;

trait PathNamespace
{
    /**
     * Get a well-formatted StudlyCase representation of path components.
     */
    public function studly_path(string $path, $ds = '/'): string
    {
        return collect(explode($ds, $this->clean_path($path, $ds)))->map(fn ($path) => Str::studly($path))->implode($ds);
    }

    /**
     * Get a well-formatted StudlyCase namespace.
     */
    public function studly_namespace(string $namespace, $ds = '\\'): string
    {
        return $this->studly_path($namespace, $ds);
    }

    /**
     * Get a well-formatted namespace from a given path.
     */
    public function path_namespace(string $path): string
    {
        return Str::of($this->studly_path($path))->replace('/', '\\')->trim('\\');
    }

    /**
     * Get a well-formatted StudlyCase namespace for a module, with an optional additional path.
     */
    public function module_namespace(string $module, ?string $path = null): string
    {
        $module_namespace = config('modules.namespace', $this->path_namespace(config('modules.paths.modules'))).'\\'.($module);
        $module_namespace .= strlen($path) ? '\\'.$this->path_namespace($path) : '';

        return $this->studly_namespace($module_namespace);
    }

    /**
     * Clean path
     */
    public function clean_path(string $path, $ds = '/'): string
    {
        return Str::of($path)->replace('\\', $ds)->explode($ds)->filter(fn ($segment) => ! empty($segment))->implode($ds);
    }

    /**
     * Get the app path basename.
     */
    public function app_path(?string $path = null): string
    {
        $default = 'app/';
        $app_path = config('modules.paths.app_folder', $default);
        $app_path = rtrim($this->clean_path(strlen($app_path) ? $app_path : $default), '/').'/';

        // Remove duplicated app_path
        if ($path) {
            $path = rtrim($this->clean_path($path), '/').'/';
            $replaces = array_filter(array_unique([$app_path, $default]), fn ($x) => Str::lower($x));

            while (Str::of(Str::lower($path))->startsWith($replaces)) {
                $path = Str::of($path)->after('/');
            }

            // Append the remaining path
            $app_path .= ltrim($path, '/');
        }

        return $this->clean_path($app_path);
    }

    /**
     * Get the app_path namespace.
     */
    public function app_path_namespace(?string $path = null): string
    {
        return $this->path_namespace($this->app_path($path));
    }

    /**
     * Get the module's app_path namespace.
     */
    public function modules_app_path_namespace(string $name, ?string $path = null): string
    {
        return $this->module_namespace($name, $this->app_path($path));
    }
}
