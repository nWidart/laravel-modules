<?php

namespace Nwidart\Modules\Publishing;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Contracts\PublisherInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;
use RuntimeException;

abstract class Publisher implements PublisherInterface
{
    /**
     * The name of module will used.
     */
    protected Module $module;

    /**
     * The modules repository instance, required by publish().
     */
    protected ?RepositoryInterface $repository = null;

    /**
     * The laravel console instance, required by publish().
     */
    protected ?Command $console = null;

    /**
     * The success message will displayed at console.
     */
    protected string $success = '';

    /**
     * The error message will displayed at console.
     */
    protected string $error = '';

    /**
     * Determine whether the result message will shown in the console.
     */
    protected bool $showMessage = true;

    /**
     * Determine whether existing files at the destination are overwritten.
     */
    protected bool $force = false;

    /**
     * The destination files the last publish() kept because they already existed.
     *
     * @var array<int, string>
     */
    protected array $skippedFiles = [];

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
     * Overwrite existing files at the destination, or keep them.
     */
    public function setForce(bool $force = true): self
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Determine whether existing files at the destination are overwritten.
     */
    public function isForced(): bool
    {
        return $this->force;
    }

    /**
     * Get the destination files the last publish() kept because they already existed.
     *
     * @return array<int, string>
     */
    public function getSkippedFiles(): array
    {
        return $this->skippedFiles;
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
     *
     * @throws RuntimeException
     */
    public function getRepository(): RepositoryInterface
    {
        if ($this->repository === null) {
            throw new RuntimeException("The 'repository' property is not set: call setRepository() on the publisher of module [{$this->module->getName()}].");
        }

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
    public function getConsole(): ?Command
    {
        return $this->console;
    }

    /**
     * Get laravel filesystem instance.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->getRepository()->getFiles();
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
     *
     * @return void
     */
    #[\Override]
    public function publish()
    {
        if ($this->console === null) {
            $message = "The 'console' property must instance of \\Illuminate\\Console\\Command.";

            throw new RuntimeException($message);
        }

        if (! $this->getFilesystem()->isDirectory($sourcePath = $this->getSourcePath())) {
            return;
        }

        if (! $this->getFilesystem()->isDirectory($destinationPath = $this->getDestinationPath())) {
            $this->getFilesystem()->makeDirectory($destinationPath, 0775, true);
        }

        $this->skippedFiles = [];

        $copied = $this->force
            ? $this->getFilesystem()->copyDirectory($sourcePath, $destinationPath)
            : $this->copyMissingFiles($sourcePath, $destinationPath);

        if ($copied) {
            if ($this->showMessage === true) {
                $this->console->outputComponents()->task($this->module->getStudlyName(), fn () => true);
            }
        } else {
            $this->console->outputComponents()->task($this->module->getStudlyName(), fn () => false);
            $this->console->outputComponents()->error($this->error);
        }
    }

    /**
     * Print the files the last publish() kept, with a hint to use --force.
     * Call it outside of a console task, so the lines do not break the task output.
     */
    public function reportSkippedFiles(): void
    {
        if ($this->console === null || $this->skippedFiles === []) {
            return;
        }

        foreach ($this->skippedFiles as $file) {
            $this->console->outputComponents()->twoColumnDetail(
                sprintf('File [%s] already exists', str_replace(base_path().'/', '', $file)),
                '<fg=yellow;options=bold>SKIPPED</>'
            );
        }

        $this->console->outputComponents()->warn('Existing files were kept. Use --force to overwrite them.');
    }

    /**
     * Copy each source file that does not exist at the destination yet, and record the others as skipped.
     * .gitkeep placeholders are neither copied nor reported.
     */
    protected function copyMissingFiles(string $sourcePath, string $destinationPath): bool
    {
        $filesystem = $this->getFilesystem();

        foreach ($filesystem->allFiles($sourcePath, true) as $file) {
            if ($file->getFilename() === '.gitkeep') {
                continue;
            }

            $target = $destinationPath.DIRECTORY_SEPARATOR.$file->getRelativePathname();

            if ($filesystem->exists($target)) {
                $this->skippedFiles[] = $target;

                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($target));

            if (! $filesystem->copy($file->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }
}
