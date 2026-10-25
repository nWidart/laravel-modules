<?php

namespace Nwidart\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;

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
     * The manifestData
     *
     * @var Collection<array-key, non-empty-array<string, mixed>>|null
     */
    private static ?Collection $manifestData = null;

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
     * Get the module.json data of the enabled modules, sorted by priority. Each entry also
     * holds the module directory under 'module_directory'.
     *
     * @return Collection<array-key, non-empty-array<string, mixed>>
     */
    public function getModulesData(): Collection
    {
        if (! empty(self::$manifestData) && ! app()->runningUnitTests()) {
            return self::$manifestData;
        }

        self::$manifestData = $this->paths
            ->flatMap(function ($path) {
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
            ->filter(fn ($module) => $this->activator->hasStatus($module['name'], true))
            ->sortBy(fn ($module) => $module['priority'] ?? 0);

        return self::$manifestData;
    }
}
