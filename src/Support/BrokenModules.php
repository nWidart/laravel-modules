<?php

namespace Nwidart\Modules\Support;

use Illuminate\Support\Str;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Throwable;

/**
 * The modules skipped because they are broken ('skip_broken_modules' config).
 *
 * The package binds one instance per application, so every module repository (custom ones
 * included), the module manifest, module:list and the "modules" health indicator share it.
 * The application runs without these modules, so an entry is never removed.
 *
 * @internal The public API is FileRepository::getBrokenModules().
 */
class BrokenModules
{
    /**
     * The modules whose module.json is not valid JSON, keyed by module.json path.
     *
     * @var array<string, array{folder: string, directory: string, reason: string}>
     */
    private array $invalidJson = [];

    /**
     * The enabled modules with a provider class that does not exist: the reason, keyed by module name.
     *
     * @var array<string, string>
     */
    private array $missingProviders = [];

    /**
     * Record a module.json file that is not valid JSON, and log it once.
     *
     * The reason shows the path relative to the application base path, not the absolute
     * path of the exception message: the health endpoint can be public.
     */
    public function recordInvalidJson(string $manifest, InvalidJsonException $exception): void
    {
        $directory = dirname($manifest);
        $reason = str_replace($manifest, $this->shownPath($manifest), $exception->getMessage());

        if (($this->invalidJson[$manifest]['reason'] ?? null) === $reason) {
            return;
        }

        $this->invalidJson[$manifest] = [
            'folder' => basename($directory),
            'directory' => $this->shownPath($directory),
            'reason' => $reason,
        ];

        $this->log(basename($directory), $reason);
    }

    /**
     * Record an enabled module with a provider class that does not exist, and log it once.
     */
    public function recordMissingProvider(string $name, string $provider): void
    {
        $reason = "Provider class [{$provider}] not found.";

        if (($this->missingProviders[$name] ?? null) === $reason) {
            return;
        }

        $this->missingProviders[$name] = $reason;

        $this->log($name, $reason);
    }

    /**
     * Get the enabled modules skipped for a provider class that does not exist: the reason, keyed by module name.
     *
     * @return array<string, string>
     */
    public function missingProviders(): array
    {
        return $this->missingProviders;
    }

    /**
     * Get the modules skipped for a module.json that is not valid JSON, keyed by label.
     *
     * Such a module has no readable name, so its label is its folder name, or its directory
     * (relative to the application base path) when that name is already taken: by a name in
     * $taken (case-insensitive, like module lookups), a module skipped for a missing provider,
     * or another invalid module.json.
     *
     * @param  array<int, string>  $taken  Names already in use, for example the names of the scanned modules.
     * @return array<string, array{folder: string, reason: string}>
     */
    public function invalidJson(array $taken = []): array
    {
        $used = array_fill_keys(array_map('strtolower', [...$taken, ...array_keys($this->missingProviders)]), true);
        $modules = [];

        foreach ($this->invalidJson as $entry) {
            $label = isset($used[strtolower($entry['folder'])]) ? $entry['directory'] : $entry['folder'];
            $used[strtolower($label)] = true;

            $modules[$label] = ['folder' => $entry['folder'], 'reason' => $entry['reason']];
        }

        return $modules;
    }

    /**
     * Get every skipped module: the reason, keyed by label (see invalidJson() for the labels).
     *
     * @param  array<int, string>  $taken  Names already in use, for example the names of the scanned modules.
     * @return array<string, string>
     */
    public function all(array $taken = []): array
    {
        return $this->missingProviders + array_map(fn (array $entry) => $entry['reason'], $this->invalidJson($taken));
    }

    /**
     * Determine whether no module was skipped.
     */
    public function isEmpty(): bool
    {
        return $this->invalidJson === [] && $this->missingProviders === [];
    }

    /**
     * Get a path relative to the application base path, or its last two segments when it is outside.
     */
    private function shownPath(string $path): string
    {
        $basePath = rtrim(app()->basePath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (Str::startsWith($path, $basePath)) {
            return Str::after($path, $basePath);
        }

        return basename(dirname($path)).DIRECTORY_SEPARATOR.basename($path);
    }

    /**
     * Write one error entry naming the module and the reason.
     *
     * Modules are skipped while the package registers, so a logger that cannot be resolved
     * or cannot write must not stop the boot: any error it throws is ignored.
     */
    private function log(string $name, string $reason): void
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
