<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Support\GeneratorRun;

class FileGenerator extends Generator
{
    /**
     * The path wil be used.
     */
    protected string $path;

    /**
     * The contens will be used.
     */
    protected string $contents;

    /**
     * The laravel filesystem or null.
     */
    protected ?Filesystem $filesystem;

    /**
     * Overwrite File
     */
    private bool $overwriteFile = false;

    /**
     * The constructor.
     *
     * @param  Filesystem|null  $filesystem
     */
    public function __construct(string $path, string $contents, $filesystem = null)
    {
        $this->path = $path;
        $this->contents = $contents;
        $this->filesystem = $filesystem ?: new Filesystem;
    }

    /**
     * Get contents.
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set contents.
     */
    public function setContents(string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get filesystem.
     *
     * @return Filesystem|null
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Set filesystem.
     */
    public function setFilesystem(Filesystem $filesystem): self
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path.
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function withFileOverwrite(bool $overwrite): FileGenerator
    {
        $this->overwriteFile = $overwrite;

        return $this;
    }

    /**
     * Generate the file.
     *
     * Every file a generator command writes goes through here. During a generator run
     * (Support\GeneratorRun) the file is recorded: in a dry run only recorded, in a real
     * run recorded with its result once the write is done.
     *
     * @return int|bool The result of Filesystem::put(); true in a dry run.
     *
     * @throws FileAlreadyExistException
     */
    public function generate()
    {
        $path = $this->getPath();
        $run = GeneratorRun::current();
        $existsOnDisk = $this->filesystem->exists($path);
        $exists = $run?->existsWhenWritten($path, $existsOnDisk) ?? $existsOnDisk;
        $existedBefore = $run?->existedBefore($path, $existsOnDisk) ?? $existsOnDisk;

        if ($exists && $this->overwriteFile !== true) {
            $run?->recordFile($path, $existedBefore, GeneratorRun::KEEP, GeneratorRun::EXISTS);

            throw new FileAlreadyExistException('File already exists!');
        }

        $action = $exists ? GeneratorRun::REPLACE : GeneratorRun::CREATE;

        if ($run === null) {
            return $this->filesystem->put($path, $this->getContents());
        }

        if ($run->isDryRun()) {
            $run->recordFile($path, $existedBefore, $action);

            return true;
        }

        try {
            $written = $this->filesystem->put($path, $this->getContents());
        } catch (\Throwable $e) {
            // The command reports the exception; the entry must not claim the file was written.
            $run->recordFile($path, $existedBefore, $action, GeneratorRun::FAILED);

            throw $e;
        }

        if ($written === false) {
            $run->recordFile($path, $existedBefore, $action, GeneratorRun::FAILED);
            $run->addError("Could not write file {$path}.");

            return false;
        }

        $run->recordFile($path, $existedBefore, $action, $exists ? GeneratorRun::REPLACED : GeneratorRun::CREATED);

        return $written;
    }
}
