<?php

namespace Nwidart\Modules\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Str;

/**
 * One run of a generator command, with the generators it calls.
 *
 * The outermost generator binds the run in the container while it runs (see
 * Traits\PlansGeneratorRun); nested generators find it there with current(). Every
 * file a generator writes goes through FileGenerator::generate(), which asks the run
 * whether to write and records each file in write order.
 *
 * In a dry run nothing is written: the run records the planned writes, the module
 * folders that --force would delete first, and the modules that module:make would
 * create, so the generators it calls can resolve a module that is not on disk yet.
 *
 * In a real run (--json without --dry-run) each entry also gets the result of the write.
 */
class GeneratorRun
{
    public const CREATE = 'create';

    public const REPLACE = 'replace';

    public const KEEP = 'keep';

    public const DELETE = 'delete';

    public const CREATED = 'created';

    public const REPLACED = 'replaced';

    public const EXISTS = 'exists';

    public const DELETED = 'deleted';

    public const FAILED = 'failed';

    /**
     * The recorded files, keyed by normalized path, in write order.
     *
     * @var array<string, array{path: string, exists: bool, action: string, result?: string}>
     */
    private array $files = [];

    /**
     * The files that were in a deleted folder before it was deleted, normalized, as keys.
     *
     * @var array<string, true>
     */
    private array $deletedFiles = [];

    /**
     * The folders deleted before anything is written, normalized.
     *
     * @var list<string>
     */
    private array $deletedFolders = [];

    /**
     * The modules this run creates, keyed by lower-case studly name: [name, path].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $plannedModules = [];

    /**
     * The error messages the generators printed.
     *
     * @var list<string>
     */
    private array $errors = [];

    public function __construct(
        private readonly string $command,
        private readonly bool $dryRun = true,
    ) {}

    /**
     * The run bound in the container, or null when no generator run is active.
     */
    public static function current(): ?self
    {
        $container = Container::getInstance();

        return $container->bound(self::class) ? $container->make(self::class) : null;
    }

    /**
     * Whether this run writes nothing and only records the planned writes.
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * Whether a file at the given path exists when the run writes it: it exists on disk
     * and no folder deleted first contains it, or this run already wrote it.
     */
    public function existsWhenWritten(string $path, bool $existsOnDisk): bool
    {
        $path = self::normalizePath($path);

        if (isset($this->files[$path])) {
            return true;
        }

        return $existsOnDisk && ! $this->isDeleted($path);
    }

    /**
     * Whether a file was on disk before the run: it is on disk now, or it was in a
     * folder this run deleted.
     */
    public function existedBefore(string $path, bool $existsOnDisk): bool
    {
        return $existsOnDisk || isset($this->deletedFiles[self::normalizePath($path)]);
    }

    /**
     * Record a file write and, in a real run, its result. A path that is already
     * recorded keeps its first entry. A dry run records no result.
     */
    public function recordFile(string $path, bool $exists, string $action, ?string $result = null): void
    {
        $path = self::normalizePath($path);

        if (isset($this->files[$path])) {
            return;
        }

        $this->files[$path] = ['path' => $path, 'exists' => $exists, 'action' => $action];

        if (! $this->dryRun && $result !== null) {
            $this->files[$path]['result'] = $result;
        }
    }

    /**
     * Record that a folder is deleted before the files are written.
     *
     * A real run passes the files in the folder, listed before it is deleted, so the
     * entries written into it afterwards still tell that they existed before the run.
     *
     * @param  list<string>  $files
     */
    public function recordDeletedFolder(string $path, array $files = []): void
    {
        $path = self::normalizePath($path);

        $this->deletedFolders[] = $path;

        foreach ($files as $file) {
            $this->deletedFiles[self::normalizePath($file)] = true;
        }

        $this->recordFile($path, true, self::DELETE, self::DELETED);
    }

    /**
     * Record a module this run creates, so module lookups find it before it is on disk.
     */
    public function planModule(string $name, string $path): void
    {
        $this->plannedModules[strtolower(Str::studly($name))] = [Str::studly($name), self::normalizePath($path)];
    }

    /**
     * The planned module for a name, as [studly name, path], matched like FileRepository::find().
     *
     * @return array{0: string, 1: string}|null
     */
    public function plannedModule(string $name): ?array
    {
        return $this->plannedModules[strtolower($name)] ?? $this->plannedModules[strtolower(Str::studly($name))] ?? null;
    }

    /**
     * Record an error message a generator printed.
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * The recorded files in write order.
     *
     * @return list<array{path: string, exists: bool, action: string, result?: string}>
     */
    public function files(): array
    {
        return array_values($this->files);
    }

    /**
     * The recorded error messages.
     *
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * The run as the --json document.
     *
     * @return array{command: string, dry_run: bool, files: list<array{path: string, exists: bool, action: string, result?: string}>, errors: list<string>}
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'dry_run' => $this->dryRun,
            'files' => $this->files(),
            'errors' => $this->errors,
        ];
    }

    /**
     * Make a path absolute, with forward slashes, single separators and no "." or ".." segments.
     */
    public static function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (! str_starts_with($path, '/') && preg_match('/^[A-Za-z]:\//', $path) !== 1) {
            $path = str_replace('\\', '/', (string) getcwd()).'/'.$path;
        }

        $prefix = preg_match('/^[A-Za-z]:\//', $path) === 1 ? substr($path, 0, 2) : '';
        $segments = [];

        foreach (explode('/', substr($path, strlen($prefix))) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.'/'.implode('/', $segments);
    }

    /**
     * Whether a deleted folder contains the given normalized path.
     */
    private function isDeleted(string $path): bool
    {
        foreach ($this->deletedFolders as $folder) {
            if ($path === $folder || str_starts_with($path, rtrim($folder, '/').'/')) {
                return true;
            }
        }

        return false;
    }
}
