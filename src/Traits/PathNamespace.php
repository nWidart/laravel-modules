<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Str;

trait PathNamespace
{
    /**
     * Get a well-formatted StudlyCase representation of path components.
     */
    public function studly_path(string $path, $directory_separator = '/'): string
    {
        return collect(explode($directory_separator, Str::of($path)
            ->replace("{$directory_separator}{$directory_separator}", $directory_separator)->trim($directory_separator)))
            ->map(fn ($path) => Str::studly($path))
            ->implode($directory_separator);
    }

    /**
     * Get a well-formatted StudlyCase namespace.
     */
    public function studly_namespace(string $namespace, $directory_separator = '\\'): string
    {
        return $this->studly_path($namespace, $directory_separator);
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
    public function module_namespace(string $module, string $path = null): string
    {
        $module_namespace = config('modules.namespace', $this->path_namespace(config('modules.paths.modules'))) . '\\' . ($module);
        $module_namespace .= strlen($path) ? '\\' . $this->path_namespace($path) : '';

        return $this->studly_namespace($module_namespace);
    }
}
