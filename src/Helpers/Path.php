<?php

namespace Nwidart\Modules\Helpers;

use Illuminate\Support\Str;

/**
 * Contains utility methods for handling path and namespace strings.
 *
 * @see Symfony\Component\Filesystem\Path
 */
class Path
{
    /**
     * Clean up the given path/namespace, replacing directory separators.
     */
    public static function clean(string $path, string $ds = DIRECTORY_SEPARATOR, string $replace = '\\'): string
    {
        if ($ds === $replace) {
            $replace = ($ds === '/') ? '\\' : '/';
        }

        return Str::of($path)->rtrim($ds)
            ->replace($replace, $ds)
            ->explode($ds)
            ->filter(fn ($segment, $key) => $key == 0 or ! empty($segment))
            ->implode($ds);
    }

    /**
     * Get a StudlyCase representation of the given path/namespace.
     */
    public static function studly(string $path, $ds = '/'): string
    {
        return collect(explode($ds, Path::clean($path, $ds)))->map(fn ($path) => Str::studly($path))->implode($ds);
    }
}
