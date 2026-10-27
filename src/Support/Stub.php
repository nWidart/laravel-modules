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
     *
     * @var array<string, scalar|\Stringable|null>
     */
    protected array $replaces = [];

    /**
     * The conditional sections, keyed by tag name.
     *
     * @var array<string, bool>
     */
    protected array $sections = [];

    /**
     * The contructor.
     *
     * @param  array<string, scalar|\Stringable|null>  $replaces
     */
    public function __construct(string $path, array $replaces = [])
    {
        $this->path = $path;
        $this->replaces = $replaces;
    }

    /**
     * Create new self instance.
     *
     * @param  array<string, scalar|\Stringable|null>  $replaces
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
     *
     * @return void
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
        $contents = $this->applySections(file_get_contents($this->getPath()));

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
     *
     * @param  array<string, scalar|\Stringable|null>  $replaces
     */
    public function replace(array $replaces = []): self
    {
        $this->replaces = $replaces;

        return $this;
    }

    /**
     * Get replacements.
     *
     * @return array<string, scalar|\Stringable|null>
     */
    public function getReplaces(): array
    {
        return $this->replaces;
    }

    /**
     * Set the conditional sections, keyed by tag name.
     *
     * A section is a block of lines wrapped in marker lines:
     *
     *     %%START_TAG%%
     *     ...
     *     %%END_TAG%%
     *
     * Each marker must be on its own line; leading and trailing spaces or tabs
     * are allowed. A tag set to true keeps the block and drops only the marker
     * lines. A tag set to false drops the whole block, marker lines included.
     * Tags that are not set here, and any other `%...%` text, are left as-is.
     *
     * @param  array<string, bool>  $sections
     */
    public function sections(array $sections = []): self
    {
        $this->sections = array_map(fn ($enabled) => (bool) $enabled, $sections);

        return $this;
    }

    /**
     * Get the conditional sections.
     *
     * @return array<string, bool>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Keep or drop the conditional sections set with sections().
     *
     * Runs on the raw stub, before token replacement, so replacement values
     * can never add or remove a section.
     */
    protected function applySections(string $contents): string
    {
        foreach ($this->sections as $tag => $enabled) {
            $tag = preg_quote((string) $tag, '/');
            $start = '^[ \t]*%%START_'.$tag.'%%[ \t]*(?:\R|\z)';
            $end = '^[ \t]*%%END_'.$tag.'%%[ \t]*(?:\R|\z)';

            $contents = $enabled
                ? preg_replace('/'.$start.'|'.$end.'/m', '', $contents)
                : preg_replace('/'.$start.'.*?'.$end.'/ms', '', $contents);
        }

        return $contents;
    }

    /**
     * Handle magic method __toString.
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->render();
    }
}
