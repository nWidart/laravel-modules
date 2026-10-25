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
     */
    public Collection $paths;

    /**
     * The manifest path.
     */
    public ?string $manifestPath;

    /**
     * The manifestData
     */
    private static ?Collection $manifestData;

    /**
     * The loaded manifest array.
     */
    private array $manifest = [];

    /**
     * module activator class
     */
    private ActivatorInterface $activator;

    /**
     * Create a new package manifest instance.
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
        return $this->getModulesData()
            ->pluck('aliases')
            ->filter(fn ($aliases) => is_array($aliases))
            ->collapse()
            ->all();
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

    public function getModulesData(): Collection
    {
        if (! empty(self::$manifestData) && ! app()->runningUnitTests()) {
            return self::$manifestData;
        }

        self::$manifestData = $this->paths
            ->flatMap(function ($path) {
                $manifests = $this->files->glob("{$path}/module.json");
                is_array($manifests) || $manifests = [];

                return collect($manifests)
                    ->map(function ($manifest) {
                        return [
                            'module_directory' => dirname($manifest),
                            ...$this->files->json($manifest),
                        ];
                    });
            })
            ->filter(fn ($module) => $this->activator->hasStatus($module['name'], true))
            ->sortBy(fn ($module) => $module['priority'] ?? 0);

        return self::$manifestData;
    }
}
