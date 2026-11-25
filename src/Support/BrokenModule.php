<?php

namespace Nwidart\Modules\Support;

use Illuminate\Support\Str;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Throwable;

/**
 * Helpers for a module skipped because it is broken ('skip_broken_modules' config).
 *
 * @internal
 */
final class BrokenModule
{
    /**
     * Get the reason recorded for a module.json file that is not valid JSON.
     *
     * The reason is shown by the health endpoint, which can be public, so the absolute path
     * in the exception message is replaced: by the path relative to the application base
     * path, or by "{folder}/module.json" for a file outside it.
     */
    public static function invalidJsonReason(string $manifest, InvalidJsonException $exception): string
    {
        $basePath = rtrim(app()->basePath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $shownPath = Str::startsWith($manifest, $basePath)
            ? Str::after($manifest, $basePath)
            : basename(dirname($manifest)).DIRECTORY_SEPARATOR.basename($manifest);

        return str_replace($manifest, $shownPath, $exception->getMessage());
    }

    /**
     * Write one error entry naming the module and the reason.
     *
     * Modules are skipped while the package registers, so a logger that cannot be resolved
     * or cannot write must not stop the boot: any error it throws is ignored.
     */
    public static function log(string $name, string $reason): void
    {
        try {
            app('log')->error("Module [{$name}] was skipped because it is broken: {$reason}", [
                'module' => $name,
                'reason' => $reason,
            ]);
        } catch (Throwable) {
            // The module is still recorded as broken; only the log entry is lost.
        }
    }
}
