<?php

namespace Nwidart\Modules\Support;

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
     * The removal tags array.
     */
    protected array $removalTags = [];

    /**
     * The contructor.
     */
    public function __construct(string $path, array $replaces = [], ?array $removalTags = null)
    {
        $this->path = $path;
        $this->replaces = $replaces;
        $this->removalTags = $removalTags ?? [];
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
        $path = static::getBasePath() . $this->path;

        return file_exists($path) ? $path : __DIR__ . '/../Commands/stubs' . $this->path;
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
     */
    public function getContents(): string
    {
        $contents = file_get_contents($this->getPath());

        foreach ($this->replaces as $search => $replace) {
            $contents = str_replace('$' . strtoupper($search) . '$', $replace, $contents);
        }

        foreach ($this->removalTags as $removalTag) {
            $contents = $this->removeContentsBetweenTagMarkers($removalTag, $contents);
        }

        return $this->cleanUpTagMarkers($contents);
    }

    /**
     * Remove content between %START_TAG% and %END_TAG%
     */
    private function removeContentsBetweenTagMarkers(string $tag, string $contents): string
    {
        return preg_replace('/%START_' . $tag . '%.*?%END_' . $tag . '%/s', '', $contents);
    }

    /**
     * Remove leftover %TAG% markers
     */
    private function cleanUpTagMarkers(string $contents): string
    {
        return preg_replace('/%[A-Z0-9_]+%/i', '', $contents);
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
        return file_put_contents($path . '/' . $filename, $this->getContents());
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
     * Set removal tags array.
     */
    public function setRemovalTags(array $tagsArray): self
    {
        $this->removalTags = $tagsArray;

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
