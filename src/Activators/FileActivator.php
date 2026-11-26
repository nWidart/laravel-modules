<?php

namespace Nwidart\Modules\Activators;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Module;

class FileActivator implements ActivatorInterface
{
    /**
     * Laravel Filesystem instance
     */
    private Filesystem $files;

    /**
     * Laravel config instance
     */
    private Config $config;

    /**
     * Array of modules activation statuses
     *
     * @var array<string, bool>
     */
    private array $modulesStatuses;

    /**
     * File used to store activation statuses
     */
    private string $statusesFile;

    public function __construct(Container $app)
    {
        $this->files = $app->make(Filesystem::class);
        $this->config = $app->make(Config::class);
        $this->statusesFile = $this->config('statuses-file');
        $this->modulesStatuses = $this->readJson();
    }

    /**
     * Get the path of the file where statuses are stored
     */
    public function getStatusesFilePath(): string
    {
        return $this->statusesFile;
    }

    /**
     * Get the stored activation statuses, keyed by module name.
     *
     * The statuses are read from the statuses file once, when the activator is created.
     *
     * @return array<string, bool>
     */
    public function getStatuses(): array
    {
        return $this->modulesStatuses;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function reset(): void
    {
        if ($this->files->exists($this->statusesFile)) {
            $this->files->delete($this->statusesFile);
        }
        $this->modulesStatuses = [];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function enable(Module $module): void
    {
        $this->setStatus($module->getName(), true, $this->folderKey($module));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function disable(Module $module): void
    {
        $this->setStatus($module->getName(), false, $this->folderKey($module));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function hasStatus(Module|string $module, bool $status): bool
    {
        // A read uses the name only, so boot (which passes a name) and a Module agree on the status.
        $key = $this->findStatusKey($module instanceof Module ? $module->getName() : $module);

        if ($key === null) {
            return $status === false;
        }

        return ($this->modulesStatuses[$key] ?? false) === $status;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function setActive(Module $module, bool $active): void
    {
        $this->setStatus($module->getName(), $active, $this->folderKey($module));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function setActiveByName(string $name, bool $status): void
    {
        $this->setStatus($name, $status);
    }

    /**
     * Store the status of a module under the key findStatusKey() resolves, or under its name when no key matches.
     */
    private function setStatus(string $name, bool $status, ?string $preferredKey = null): void
    {
        $key = $this->findStatusKey($name, $preferredKey) ?? $name;

        // Drop other case variants of the same module, so a file with duplicates converges to one entry.
        foreach (array_keys($this->modulesStatuses) as $storedKey) {
            if ((string) $storedKey !== $key && $this->sameModuleName((string) $storedKey, $name)) {
                unset($this->modulesStatuses[$storedKey]);
            }
        }

        $this->modulesStatuses[$key] = $status;
        $this->writeJson();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function delete(Module $module): void
    {
        $removed = false;

        foreach (array_keys($this->modulesStatuses) as $storedKey) {
            if ($this->sameModuleName((string) $storedKey, $module->getName())) {
                unset($this->modulesStatuses[$storedKey]);
                $removed = true;
            }
        }

        if ($removed) {
            $this->writeJson();
        }
    }

    /**
     * Find the key under which the status of a module is stored.
     *
     * The repository matches module names case-insensitively, so a status stored as "Blog" belongs to a module named
     * "blog". The preferred key wins when it is stored and names the same module, then an exact match of the name,
     * then the first key in file order that matches case-insensitively.
     */
    private function findStatusKey(string $name, ?string $preferredKey = null): ?string
    {
        foreach ([$preferredKey, $name] as $candidate) {
            if ($candidate !== null && $this->sameModuleName($candidate, $name) && array_key_exists($candidate, $this->modulesStatuses)) {
                return $candidate;
            }
        }

        foreach (array_keys($this->modulesStatuses) as $storedKey) {
            if ($this->sameModuleName((string) $storedKey, $name)) {
                return (string) $storedKey;
            }
        }

        return null;
    }

    /**
     * Get the folder name of a module, the key the Vite module loader and module:update-phpunit-coverage look up.
     *
     * Only writes prefer it, to choose which case variant they keep.
     */
    private function folderKey(Module $module): string
    {
        return basename($module->getPath());
    }

    /**
     * Whether two module names refer to the same module, the way the repository compares them.
     */
    private function sameModuleName(string $first, string $second): bool
    {
        return strtolower($first) === strtolower($second);
    }

    /**
     * Writes the activation statuses in a file, as json
     */
    private function writeJson(): void
    {
        $this->files->put($this->statusesFile, json_encode($this->modulesStatuses, JSON_PRETTY_PRINT));
    }

    /**
     * Reads the json file that contains the activation statuses.
     *
     * A file that is not valid JSON is never treated as empty: the next write would replace it and lose every status.
     *
     * @return array<string, mixed>
     *
     * @throws FileNotFoundException
     * @throws InvalidJsonException
     */
    private function readJson(): array
    {
        if (! $this->files->exists($this->statusesFile)) {
            return [];
        }

        $contents = $this->files->get($this->statusesFile);

        if (trim($contents) === '') {
            return [];
        }

        $statuses = json_decode($contents, true);

        // A non-empty JSON list has no module names; an empty one ([]) simply holds no statuses.
        if (! is_array($statuses) || ($statuses !== [] && array_is_list($statuses))) {
            $error = json_last_error() === JSON_ERROR_NONE ? 'Expected a JSON object' : json_last_error_msg();

            throw new InvalidJsonException("The module statuses file [{$this->statusesFile}] is not valid: {$error}. Fix or restore the file (it maps module names to true or false).");
        }

        return $statuses;
    }

    /**
     * Reads a config parameter under the 'activators.file' key
     */
    private function config(string $key, mixed $default = null): mixed
    {
        return $this->config->get('modules.activators.file.'.$key, $default);
    }
}
