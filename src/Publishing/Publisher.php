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
     * The name of module will used.
     */
    protected Module $module;

    /**
     * The modules repository instance.
     */
    protected RepositoryInterface $repository;

    /**
     * The laravel console instance.
     */
    protected Command $console;

    /**
     * The success message will displayed at console.
     */
    protected string $success;

    /**
     * The error message will displayed at console.
     */
    protected string $error = '';

    /**
     * Determine whether the result message will shown in the console.
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
     */
    public function showMessage(): self
    {
        $this->showMessage = true;

        return $this;
    }

    /**
     * Hide the result message.
     */
    public function hideMessage(): self
    {
        $this->showMessage = false;

        return $this;
    }

    /**
     * Get module instance.
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Set modules repository instance.
     */
    public function setRepository(RepositoryInterface $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get modules repository instance.
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Set console instance.
     */
    public function setConsole(Command $console): self
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Get console instance.
     */
    public function getConsole(): Command
    {
        return $this->console;
    }

    /**
     * Get laravel filesystem instance.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->repository->getFiles();
    }

    /**
     * Get destination path.
     */
    abstract public function getDestinationPath(): string;

    /**
     * Get source path.
     */
    abstract public function getSourcePath(): string;

    /**
     * Publish something.
     */
    public function publish()
    {
        if (! $this->console instanceof Command) {
            $message = "The 'console' property must instance of \\Illuminate\\Console\\Command.";

            throw new \RuntimeException($message);
        }

        if (! $this->getFilesystem()->isDirectory($sourcePath = $this->getSourcePath())) {
            return;
        }

        if (! $this->getFilesystem()->isDirectory($destinationPath = $this->getDestinationPath())) {
            $this->getFilesystem()->makeDirectory($destinationPath, 0775, true);
        }

        if ($this->getFilesystem()->copyDirectory($sourcePath, $destinationPath)) {
            if ($this->showMessage === true) {
                $this->console->components->task($this->module->getStudlyName(), fn () => true);
            }
        } else {
            $this->console->components->task($this->module->getStudlyName(), fn () => false);
            $this->console->components->error($this->error);
        }
    }
}
