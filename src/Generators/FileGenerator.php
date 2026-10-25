<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;

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
    private bool $overwriteFile;

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
     * @return int|bool The result of Filesystem::put().
     *
     * @throws FileAlreadyExistException
     */
    public function generate()
    {
        $path = $this->getPath();
        if (! $this->filesystem->exists($path)) {
            return $this->filesystem->put($path, $this->getContents());
        }
        if ($this->overwriteFile === true) {
            return $this->filesystem->put($path, $this->getContents());
        }

        throw new FileAlreadyExistException('File already exists!');
    }
}
