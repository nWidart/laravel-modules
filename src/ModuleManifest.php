<?php

namespace Nwidart\Modules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Support\BrokenModules;

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
            ->flatMap(fn (array $module) => self::providersOf($module))
            ->values()
            ->all();
    }

    /**
     * Get the providers a module lists: its 'providers' entry flattened, without empty values.
     *
     * @param  array<string, mixed>  $module
     * @return list<mixed>
     */
    private static function providersOf(array $module): array
    {
        return collect([$module['providers'] ?? null])->flatten()->filter()->values()->all();
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
     * When the 'skip_broken_modules' config is true, a module whose module.json is not valid
     * JSON, or an enabled module with a provider class that does not exist, is left out and
     * recorded in the BrokenModules store.
     *
     * @return Collection<array-key, non-empty-array<string, mixed>>
     */
    public function getModulesData(): Collection
    {
        $repository = $this->repository();

        if ($repository === null) {
            if ($this->manifestData !== null) {
                return $this->manifestData;
            }

            $skipBroken = $this->skipsBrokenModules();

            $modules = $this->paths->flatMap(function ($path) use ($skipBroken) {
                // Filesystem::glob() returns false on error, despite its array return type.
                $manifests = $this->files->glob("{$path}/module.json") ?: [];

                return collect($manifests)->map(fn ($manifest) => $this->readModuleJson($manifest, $skipBroken));
            });

            /** @var Collection<array-key, non-empty-array<string, mixed>> $modules */
            $modules = $skipBroken ? $modules->filter() : $modules;

            return $this->manifestData = $this->buildModulesData($modules, $skipBroken);
        }

        // Rebuild when the repository scanned again, for example after resetModules().
        $files = $repository->getModuleJsonFiles();

        if ($this->manifestData === null || $this->manifestSource !== $files) {
            $this->manifestSource = $files;
            $this->manifestData = $this->buildModulesData(
                collect($files)->map(fn (array $file) => [
                    'module_directory' => dirname($file['path']),
                    ...$file['json']->getAttributes(),
                ]),
                $this->skipsBrokenModules()
            );
        }

        return $this->manifestData;
    }

    /**
     * Read a module.json file in the manifest's own paths, with the module directory under 'module_directory'.
     *
     * With $skipBroken, a file that is not valid JSON is recorded in the BrokenModules store
     * and null is returned.
     *
     * @return non-empty-array<string, mixed>|null
     */
    private function readModuleJson(string $manifest, bool $skipBroken): ?array
    {
        if (! $skipBroken) {
            // Filesystem::json() is typed as a plain array; module.json holds a JSON object.
            /** @var array<string, mixed> $json */
            $json = $this->files->json($manifest);

            return [
                'module_directory' => dirname($manifest),
                ...$json,
            ];
        }

        try {
            $json = Json::make($manifest, $this->files)->getAttributes();
        } catch (InvalidJsonException $exception) {
            app(BrokenModules::class)->recordInvalidJson($manifest, $exception);

            return null;
        }

        return [
            'module_directory' => dirname($manifest),
            ...$json,
        ];
    }

    /**
     * Keep the enabled modules and sort them by priority.
     *
     * With $skipBroken, an enabled module with a provider class that does not exist is left
     * out as a whole (providers, aliases and files) and recorded as broken.
     *
     * @param  Collection<array-key, non-empty-array<string, mixed>>  $modules
     * @return Collection<array-key, non-empty-array<string, mixed>>
     */
    private function buildModulesData(Collection $modules, bool $skipBroken): Collection
    {
        $modules = $modules->filter(fn ($module) => $this->activator->hasStatus($module['name'], true));

        if ($skipBroken) {
            $modules = $modules->reject(fn ($module) => $this->hasMissingProvider($module));
        }

        return $modules->sortBy(fn ($module) => $module['priority'] ?? 0);
    }

    /**
     * Determine whether a provider of the module does not exist, and record the module as broken if so.
     *
     * A provider may be an alias that the module's own 'aliases' map to a class. Aliases are
     * registered after this check, so the aliased class is checked instead.
     *
     * @param  non-empty-array<string, mixed>  $module
     */
    private function hasMissingProvider(array $module): bool
    {
        $aliases = is_array($module['aliases'] ?? null) ? $module['aliases'] : [];

        foreach (self::providersOf($module) as $provider) {
            if (is_string($provider) && (class_exists($provider) || (is_string($aliases[$provider] ?? null) && class_exists($aliases[$provider])))) {
                continue;
            }

            app(BrokenModules::class)->recordMissingProvider(
                (string) $module['name'],
                is_scalar($provider) ? (string) $provider : get_debug_type($provider)
            );

            return true;
        }

        return false;
    }

    /**
     * Determine whether broken modules are skipped instead of stopping the boot.
     */
    private function skipsBrokenModules(): bool
    {
        return (bool) app(ConfigRepository::class)->get('modules.skip_broken_modules', false);
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
