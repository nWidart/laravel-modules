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
        $this->setActiveByName($module->getName(), true);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function disable(Module $module): void
    {
        $this->setActiveByName($module->getName(), false);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function hasStatus(Module|string $module, bool $status): bool
    {
        $name = $module instanceof Module ? $module->getName() : $module;

        if (! isset($this->modulesStatuses[$name])) {
            return $status === false;
        }

        return $this->modulesStatuses[$name] === $status;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function setActive(Module $module, bool $active): void
    {
        $this->setActiveByName($module->getName(), $active);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function setActiveByName(string $name, bool $status): void
    {
        $this->modulesStatuses[$name] = $status;
        $this->writeJson();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function delete(Module $module): void
    {
        if (! isset($this->modulesStatuses[$module->getName()])) {
            return;
        }
        unset($this->modulesStatuses[$module->getName()]);
        $this->writeJson();
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
