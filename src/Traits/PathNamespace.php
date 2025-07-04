<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;
use Nwidart\Modules\Helpers\Path;

trait PathNamespace
{
    /**
     * Generate a well-formatted StudlyCase namespace from the given path.
     */
    public function path_namespace(string $path): string
    {
        return Path::studly($this->is_app_path($path) ? $this->app_path($path) : $path, '\\');
    }

    /**
     * Get the path to the modules directory.
     */
    public function modules_path(?string $path = null): string
    {
        $base = config('modules.paths.modules');

        return $path ? $base.DIRECTORY_SEPARATOR.$this->path($path) : $base;
    }

    public function module_path(string $module = 'Blog', ?string $path = null): string
    {
        $module = module($module, true);

        return $path ? $module->path($this->path($path)) : $module->getPath();
    }

    public function module_app_path(string $module = 'Blog', ?string $path = null): string
    {
        $module = module($module, true);

        return $module->path($this->app_path($path));
    }

    /**
     * Get a well-formatted StudlyCase namespace for the module, optionally appending the given path.
     */
    public function module_namespace(string $module, ?string $path = null): string
    {
        $module_namespace = rtrim(config('modules.namespace') ?? config('modules.paths.modules'), '\\').'\\'.($module);
        if (! empty($path)) {
            $module_namespace .= '\\'.trim($path, '\\');
        }

        return $this->path_namespace($module_namespace);
    }

    /**
     * Format the given namespace and determine if it's within the app path.
     */
    public function namespace(string $namespace): string
    {
        return $this->is_app_path($namespace) ? $this->app_path_namespace($namespace) : $this->path_namespace($namespace);
    }

    /**
     * Format the given path and determine if it's within the app path.
     */
    public function path(string $path): string
    {
        return $this->is_app_path($path) ? $this->app_path($path) : Path::clean($path);
    }

    /**
     * Get the base name of the app path.
     */
    public function app_path(?string $path = null): string
    {
        $default = 'app/';
        $app_path = config('modules.paths.app') ?? config('modules.paths.app_folder');

        if (empty($app_path)) {
            $app_path = $default;
        }

        $app_path = trim(Path::clean($app_path), '/').'/';

        // Remove duplicated app_path
        if ($path) {
            $path = trim(Path::clean($path), '/').'/';

            while ($this->is_app_path($path)) {
                $path = Str::of($path)->after('/');
            }

            // Append the extra path
            $app_path .= ltrim($path, '/');
        }

        return Path::clean(trim($app_path, '/'));
    }

    /**
     * Determine whether the given path is within the app path.
     */
    public function is_app_path(string $path): bool
    {
        $default = 'app/';
        $app_path = config('modules.paths.app') ?? config('modules.paths.app_folder');
        if (empty($app_path)) {
            $app_path = $default;
        }
        $app_path = trim(Path::clean($app_path), '/').'/';

        $path = trim(Path::clean($path), '/').'/';
        $replaces = array_filter(array_unique([$app_path, $default]), fn ($x) => Str::lower($x));

        if (Str::of(Str::lower($path))->startsWith($replaces)) {
            return true;
        }

        return false;
    }

    /**
     * Get the namespace for the app path.
     */
    public function app_path_namespace(?string $path = null): string
    {
        return $this->path_namespace($this->app_path($path));
    }

    /**
     * Get the namespace for the module app path.
     */
    public function modules_app_path_namespace(string $name, ?string $path = null): string
    {
        return $this->module_namespace($name, $this->app_path($path));
    }
}
