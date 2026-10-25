<?php

namespace Nwidart\Modules\Support;

/** @phpstan-consistent-constructor */
class Stub
{
    /**
     * The stub path.
     */
    protected string $path;

    /**
     * The base path of stub file.
     */
    protected static ?string $basePath = null;

    /**
     * The replacements array.
     */
    protected array $replaces = [];

    /**
     * The contructor.
     */
    public function __construct(string $path, array $replaces = [])
    {
        $this->path = $path;
        $this->replaces = $replaces;
    }

    /**
     * Create new self instance.
     */
    public static function create(string $path, array $replaces = []): self
    {
        return new static($path, $replaces);
    }

    /**
     * Set stub path.
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get stub path.
     */
    public function getPath(): string
    {
        $path = static::getBasePath().$this->path;

        return file_exists($path) ? $path : __DIR__.'/../Commands/stubs'.$this->path;
    }

    /**
     * Set base path.
     */
    public static function setBasePath(string $path)
    {
        static::$basePath = $path;
    }

    /**
     * Get base path.
     */
    public static function getBasePath(): ?string
    {
        return static::$basePath;
    }

    /**
     * Get stub contents.
     *
     * All tokens are replaced in a single pass, so a replacement value that
     * itself contains a `$TOKEN$` is written as-is and never substituted again.
     */
    public function getContents(): string
    {
        $contents = file_get_contents($this->getPath());

        $tokens = [];

        foreach ($this->replaces as $search => $replace) {
            // The first value for a token wins, as with the former sequential replacement.
            $tokens['$'.strtoupper($search).'$'] ??= (string) $replace;
        }

        return strtr($contents, $tokens);
    }

    /**
     * Get stub contents.
     */
    public function render(): string
    {
        return $this->getContents();
    }

    /**
     * Save stub to specific path.
     */
    public function saveTo(string $path, string $filename): bool
    {
        return file_put_contents($path.'/'.$filename, $this->getContents());
    }

    /**
     * Set replacements array.
     */
    public function replace(array $replaces = []): self
    {
        $this->replaces = $replaces;

        return $this;
    }

    /**
     * Get replacements.
     */
    public function getReplaces(): array
    {
        return $this->replaces;
    }

    /**
     * Handle magic method __toString.
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
