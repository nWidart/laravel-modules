<?php

namespace Nwidart\Modules;

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Nwidart\Modules\Contracts\ActivatorInterface;

class ModuleManifest
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * The base path.
     *
     * @var string
     */
    public $paths;

    /**
     * The manifest path.
     *
     * @var string|null
     */
    public $manifestPath;

    /**
     * The manifestData
     */
    private static ?Collection $manifestData;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    private $manifest;

    /**
     * module activator class
     */
    private ActivatorInterface $activator;

    /**
     * Create a new package manifest instance.
     *
     * @param  Collection  $paths
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $paths, $manifestPath, ActivatorInterface $activator)
    {
        $this->files = $files;
        $this->paths = collect($paths);
        $this->manifestPath = $manifestPath;
        $this->activator = $activator;
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providers()
    {
        return $this->config('providers');
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providersArray()
    {
        return $this->getManifest()['providers'] ?? [];
    }

    /**
     * Get all of the aliases for all packages.
     *
     * @return array
     */
    public function aliases()
    {
        return $this->config('aliases');
    }

    /**
     * Get all of the values for all packages for the given configuration name.
     *
     * @param  string  $key
     * @return array
     */
    public function config($key)
    {
        return collect($this->getManifest())->flatMap(function ($configuration) use ($key) {
            return (array)($configuration[$key] ?? []);
        })->filter()->all();
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest()
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (! is_file($this->manifestPath)) {
            $this->build();
        }

        return $this->manifest = is_file($this->manifestPath) ?
            $this->files->getRequire($this->manifestPath) : [];
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build(): void
    {
        $providers = $this->getModulesData()
            ->pluck('providers')
            ->flatten()
            ->filter()
            ->toArray();

        $this->write(
            [
                'providers' => $providers,
                'eager' => $providers,
                'deferred' => [],
            ]
        );
    }

    /**
     * Write the given manifest array to disk.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest): void
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }
        $this->files->replace(
            $this->manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }

    public function registerFiles(): void
    {
        //todo check this section store on module.php or not?
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
