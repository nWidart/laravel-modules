<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;

trait PathNamespace
{
    /**
     * Clean up path and namespace.
     */
    public function clean(string $path, string $ds = '/', string $replace = '\\'): string
    {
        if ($ds === $replace) {
            $replace = $ds === '/' ? '\\' : '/';
        }

        return Str::of($path)->trim($ds)->replace($replace, $ds)->explode($ds)->filter(fn ($segment) => ! empty($segment))->implode($ds);
    }

    /**
     * Clean up a namespace.
     */
    public function clean_namespace(string $namespace, string $ds = '\\', string $replace = '/'): string
    {
        return $this->clean($namespace, $ds, $replace);
    }

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
        return $this->studly_path(Str::of($namespace)->replace('/', $ds)->trim($ds), $ds);
    }

    /**
     * Get a well-formatted namespace from a given path.
     */
    public function path_namespace(string $path): string
    {
        return $this->studly_namespace($this->is_app_path($path) ? $this->app_path($path) : $path);
    }

    /**
     * Get a well-formatted StudlyCase namespace for a module, with an optional additional path.
     */
    public function module_namespace(string $module, ?string $path = null): string
    {
        $module_namespace = rtrim(config('modules.namespace', config('modules.paths.modules')), '\\').'\\'.($module);
        if (! empty($path)) {
            $module_namespace .= '\\'.ltrim($path, '\\');
        }

        return $this->path_namespace($module_namespace);
    }

    /**
     * Clean up a path.
     */
    public function clean_path(string $path, string $ds = '/', string $replace = '\\'): string
    {
        return $this->clean($path, $ds, $replace);
    }

    /**
     * Format a namespace.
     */
    public function namespace(string $namespace): string
    {
        return $this->is_app_path($namespace) ? $this->app_path_namespace($namespace) : $this->clean_namespace($namespace);
    }

    /**
     * Format a path.
     */
    public function path(string $path): string
    {
        return $this->is_app_path($path) ? $this->app_path($path) : $this->clean_path($path);
    }

    /**
     * Determine if the given path is app_path.
     */
    public function is_app_path(string $path): bool
    {
        $default = 'app/';
        $app_path = config('modules.paths.app', $default);
        $app_path = rtrim($this->clean_path(strlen($app_path) ? $app_path : $default), '/').'/';

        $path = rtrim($this->clean_path($path), '/').'/';
        $replaces = array_filter(array_unique([$app_path, $default]), fn ($x) => Str::lower($x));

        if (Str::of(Str::lower($path))->startsWith($replaces)) {
            return true;
        }

        return false;
    }

    /**
     * Get the app path basename.
     */
    public function app_path(?string $path = null): string
    {
        $default = 'app/';
        $app_path = config('modules.paths.app', $default);
        $app_path = rtrim($this->clean_path(strlen($app_path) ? $app_path : $default), '/').'/';

        // Remove duplicated app_path
        if ($path) {
            $path = rtrim($this->clean_path($path), '/').'/';

            while ($this->is_app_path($path)) {
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
