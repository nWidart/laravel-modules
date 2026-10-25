<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;

trait PathNamespace
{
    /**
     * Get a well-formatted StudlyCase representation of path components.
     *
     * @param  string  $ds  The separator.
     */
    public function studly_path(string $path, $ds = '/'): string
    {
        return collect(explode($ds, $this->clean_path($path, $ds)))->map(fn ($path) => Str::studly($path))->implode($ds);
    }

    /**
     * Get a well-formatted StudlyCase namespace.
     *
     * @param  string  $ds  The separator.
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
        $module_namespace .= ($path !== null && $path !== '') ? '\\'.$this->path_namespace($path) : '';

        return $this->studly_namespace($module_namespace);
    }

    /**
     * Clean path: remove empty segments, such as leading, trailing, or doubled separators.
     * Segments such as "0" are kept.
     *
     * @param  string  $ds  The separator.
     */
    public function clean_path(string $path, $ds = '/'): string
    {
        return Str::of($path)->explode($ds)->reject(fn (string $segment) => $segment === '')->implode($ds);
    }

    /**
     * Remove the configured app folder (modules.paths.app_folder) from the start of a path.
     *
     * Only a whole leading segment is removed: with app folder "app/", "app/Actions" becomes
     * "Actions", while "application/Foo" stays as it is.
     * A null or empty app folder leaves the path unchanged.
     */
    public function strip_app_folder(?string $path): string
    {
        $path = (string) $path;
        $app_folder = trim((string) config('modules.paths.app_folder'), '/');

        if ($app_folder === '') {
            return $path;
        }

        if ($path === $app_folder) {
            return '';
        }

        return str_starts_with($path, $app_folder.'/')
            ? substr($path, strlen($app_folder) + 1)
            : $path;
    }

    /**
     * Get the app path basename.
     */
    public function app_path(?string $path = null): string
    {
        $config_path = (string) config('modules.paths.app_folder');

        // Get modules config app path or use Laravel default app path.
        $app_path = $this->clean_path(strlen($config_path) ? $config_path : 'app/');

        if ($path) {
            // Remove leading custom|default app folders, compared as whole path segments.
            // The path gets shorter on every pass, so the loop always ends.
            $prefixes = array_filter(array_unique([$app_path, 'app']), fn (string $prefix) => $prefix !== '');

            do {
                $previous = $path;

                foreach ($prefixes as $prefix) {
                    if ($path === $prefix) {
                        $path = '';
                    } elseif (str_starts_with($path, $prefix.'/')) {
                        $path = substr($path, strlen($prefix) + 1);
                    }
                }
            } while ($path !== '' && $path !== $previous);

            // Append additional path
            $app_path .= strlen($path) ? '/'.$path : '';
        }

        return $this->clean_path($app_path);
    }
}
