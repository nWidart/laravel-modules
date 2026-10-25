<?php

namespace Nwidart\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;

class ModuleManifest
{
    /**
     * The filesystem instance.
     */
    private Filesystem $files;

    /**
     * The base path.
     *
     * @var Collection<int, string>
     */
    public Collection $paths;

    /**
     * The manifest path.
     */
    public ?string $manifestPath;

    /**
     * The enabled modules data, sorted by priority.
     *
     * @var Collection<array-key, non-empty-array<string, mixed>>|null
     */
    private ?Collection $manifestData = null;

    /**
     * The repository module.json files the manifest data was built from.
     *
     * @var list<array{path: string, json: Json}>|null
     */
    private ?array $manifestSource = null;

    /**
     * The loaded manifest array.
     *
     * @var array<int, string>
     */
    private array $manifest = [];

    /**
     * module activator class
     */
    private ActivatorInterface $activator;

    /**
     * Create a new package manifest instance.
     *
     * @param  array<int, string>  $paths
     */
    public function __construct(Filesystem $files, array $paths, string $manifestPath, ActivatorInterface $activator)
    {
        $this->files = $files;
        $this->paths = collect($paths);
        $this->manifestPath = $manifestPath;
        $this->activator = $activator;
    }

    /**
     * Get the current package manifest.
     *
     * @return array<int, string> The service provider classes of the enabled modules.
     */
    public function getProviders(): array
    {
        if (! empty($this->manifest)) {
            return $this->manifest;
        }

        return $this->manifest = $this->build();
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return array<int, string>
     */
    public function build(): array
    {
        return $this->getModulesData()
            ->pluck('providers')
            ->flatten()
            ->filter()
            ->toArray();
    }

    /**
     * Get the class aliases of the enabled modules, keyed by alias name.
     *
     * Modules are read in priority order, so a later module wins when two modules declare the same alias.
     *
     * @return array<string, class-string>
     */
    public function getAliases(): array
    {
        // Collection::collapse() is documented as static<int, mixed>, but it keeps the alias-name keys.
        /** @var array<string, class-string> $aliases */
        $aliases = $this->getModulesData()
            ->pluck('aliases')
            ->filter(fn ($aliases) => is_array($aliases))
            ->collapse()
            ->all();

        return $aliases;
    }

    /**
     * Register the class aliases of the enabled modules with Laravel's alias loader.
     */
    public function registerAliases(): void
    {
        $loader = AliasLoader::getInstance();

        foreach ($this->getAliases() as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    public function registerFiles(): void
    {
        // todo check this section store on module.php or not?
        $this->getModulesData()
            ->each(function (array $manifest) {
                if (empty($manifest['files'])) {
                    return;
                }

                foreach ($manifest['files'] as $file) {
                    include_once $manifest['module_directory'].DIRECTORY_SEPARATOR.$file;
                }
            });
    }

    /**
     * Get the data of every enabled module, sorted by priority.
     *
     * When the module repository scans the same paths, the data is built from the module.json
     * files the repository already read, so each file is found and read once per application.
     * Each entry also holds the module directory under 'module_directory'.
     *
     * @return Collection<array-key, non-empty-array<string, mixed>>
     */
    public function getModulesData(): Collection
    {
        $repository = $this->repository();

        if ($repository === null) {
            return $this->manifestData ??= $this->buildModulesData(
                $this->paths->flatMap(function ($path) {
                    // Filesystem::glob() returns false on error, despite its array return type.
                    $manifests = $this->files->glob("{$path}/module.json") ?: [];

                    return collect($manifests)
                        ->map(function ($manifest) {
                            // Filesystem::json() is typed as a plain array; module.json holds a JSON object.
                            /** @var array<string, mixed> $json */
                            $json = $this->files->json($manifest);

                            return [
                                'module_directory' => dirname($manifest),
                                ...$json,
                            ];
                        });
                })
            );
        }

        // Rebuild when the repository scanned again, for example after resetModules().
        $files = $repository->getModuleJsonFiles();

        if ($this->manifestData === null || $this->manifestSource !== $files) {
            $this->manifestSource = $files;
            $this->manifestData = $this->buildModulesData(
                collect($files)->map(fn (array $file) => [
                    'module_directory' => dirname($file['path']),
                    ...$file['json']->getAttributes(),
                ])
            );
        }

        return $this->manifestData;
    }

    /**
     * Keep the enabled modules and sort them by priority.
     *
     * @param  Collection<array-key, non-empty-array<string, mixed>>  $modules
     * @return Collection<array-key, non-empty-array<string, mixed>>
     */
    private function buildModulesData(Collection $modules): Collection
    {
        return $modules
            ->filter(fn ($module) => $this->activator->hasStatus($module['name'], true))
            ->sortBy(fn ($module) => $module['priority'] ?? 0);
    }

    /**
     * Get the bound module repository when it scans the same paths as this manifest.
     *
     * It is resolved lazily, so the constructor keeps its arguments. A custom repository
     * that does not extend FileRepository, or one that scans other paths, is not used:
     * the manifest then reads the module.json files in its own paths.
     */
    private function repository(): ?FileRepository
    {
        $container = app();

        if (! $container->bound(RepositoryInterface::class)) {
            return null;
        }

        $repository = $container->make(RepositoryInterface::class);

        if (! $repository instanceof FileRepository || $repository->getScanPaths() !== $this->paths->values()->all()) {
            return null;
        }

        return $repository;
    }
}
