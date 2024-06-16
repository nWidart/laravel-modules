<?php

namespace Nwidart\Modules\Publishing;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\PublisherInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

abstract class Publisher implements PublisherInterface
{
    /**
     * The name of module.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * The modules repository instance.
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * The laravel console instance.
     *
     * @var Command
     */
    protected Command $console;

    /**
     * The success message will be displayed at console.
     *
     * @var string
     */
    protected string $success;

    /**
     * The error message will be displayed at console.
     *
     * @var string
     */
    protected string $error = '';

    /**
     * Determine whether the result message will be shown in the console.
     *
     * @var bool
     */
    protected bool $showMessage = true;

    /**
     * The constructor.
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Show the result message.
     *
     * @return self
     */
    public function showMessage(): self
    {
        $this->showMessage = true;

        return $this;
    }

    /**
     * Hide the result message.
     *
     * @return self
     */
    public function hideMessage(): self
    {
        $this->showMessage = false;

        return $this;
    }

    /**
     * Get module instance.
     *
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Set modules repository instance.
     *
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository): static
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get modules repository instance.
     *
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Set console instance.
     *
     *
     * @return $this
     */
    public function setConsole(Command $console): static
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Get console instance.
     *
     * @return Command
     */
    public function getConsole(): Command
    {
        return $this->console;
    }

    /**
     * Get laravel filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->repository->getFiles();
    }

    /**
     * Get destination path.
     *
     * @return string
     */
    abstract public function getDestinationPath(): string;

    /**
     * Get source path.
     *
     * @return string
     */
    abstract public function getSourcePath(): string;

    /**
     * Publish something.
     */
    public function publish(): void
    {
        if (! $this->getFilesystem()->isDirectory($sourcePath = $this->getSourcePath())) {
            return;
        }

        if (! $this->getFilesystem()->isDirectory($destinationPath = $this->getDestinationPath())) {
            $this->getFilesystem()->makeDirectory($destinationPath, 0775, true);
        }

        if ($this->getFilesystem()->copyDirectory($sourcePath, $destinationPath)) {
            if ($this->showMessage === true) {
                $this->console->outputComponents()->task($this->module->getStudlyName(), fn () => true);
            }
        } else {
            $this->console->outputComponents()->task($this->module->getStudlyName(), fn () => false);
            $this->console->outputComponents()->error($this->error);
        }
    }
}
