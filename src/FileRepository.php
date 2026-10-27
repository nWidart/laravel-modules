<?php

namespace Nwidart\Modules;

use Countable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laravel\Lumen\Application;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Exceptions\InvalidAssetPath;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Process\Updater;
use Symfony\Component\Process\Process;

abstract class FileRepository implements Countable, RepositoryInterface
{
    use Macroable;

    /**
     * Application instance (Laravel or Lumen). The constructor accepts any Container for
     * backwards compatibility, but the repository calls application methods on it.
     *
     * @var (Container&\Illuminate\Contracts\Foundation\Application)|(Container&Application)
     */
    protected $app;

    /**
     * The module path.
     */
    protected ?string $path;

    /**
     * The scanned paths.
     *
     * @var array<int, string>
     */
    protected array $paths = [];

    /**
     * Stub path
     */
    protected ?string $stubPath = null;

    /**
     * URL Generator
     */
    private UrlGenerator $url;

    /**
     * Config Repository
     */
    private ConfigRepository $config;

    /**
     * File system
     */
    private Filesystem $files;

    /**
     * The scan paths the module.json files below were found in.
     *
     * @var list<string>
     */
    private array $scannedPaths = [];

    /**
     * The module.json files found by the last scan, in glob order: decoded once per repository instance.
     *
     * @var list<array{path: string, json: Json}>|null
     */
    private ?array $moduleJsonFiles = null;

    /**
     * The modules built from the module.json files, keyed by lower case name.
     *
     * @var array<string, Module>|null
     */
    private ?array $modules = null;

    /**
     * The constructor.
     *
     * @param  (Container&\Illuminate\Contracts\Foundation\Application)|(Container&Application)  $app
     */
    public function __construct(Container $app, ?string $path = null)
    {
        $this->app = $app;
        $this->path = $path;
        $this->url = $app->make(UrlGenerator::class);
        $this->config = $app->make(ConfigRepository::class);
        $this->files = $app->make(Filesystem::class);
    }

    /**
     * Add other module location.
     */
    public function addLocation(string $path): self
    {
        $this->paths[] = $path;

        $this->resetModules();

        return $this;
    }

    /**
     * Get all additional paths.
     *
     * @return array<int, string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Get scanned modules paths.
     *
     * @return array<int, string>
     */
    #[\Override]
    public function getScanPaths(): array
    {
        $paths = $this->paths;

        $paths[] = $this->getPath();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        $paths = array_map(fn ($path) => Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*'), $paths);

        return $paths;
    }

    /**
     * Creates a new Module instance
     *
     * @param  (Container&\Illuminate\Contracts\Foundation\Application)|(Container&Application)  $app
     */
    abstract protected function createModule(Container $app, string $name, string $path): Module;

    /**
     * Get & scan all modules.
     *
     * The result is kept for the lifetime of this repository instance (one per application),
     * so finding a module does not read every module.json again. Call resetModules() after
     * adding or removing module folders without going through the repository.
     *
     * @return array<string, Module>
     */
    #[\Override]
    public function scan(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [];

        foreach ($this->getModuleJsonFiles() as $file) {
            $json = $file['json'];
            $name = $json->get('name');

            $modules[strtolower($name)] = $this->createModule($this->app, $name, dirname($file['path']))
                ->setJson($json);
        }

        return $this->modules = $modules;
    }

    /**
     * Get every module.json file in the scan paths, in glob order, each decoded once.
     *
     * ModuleManifest builds the enabled module list from these files too, so a request
     * finds and reads each module.json only once. The files are scanned again after
     * resetModules(), or when the scan paths changed since the last scan (config set
     * after the package registered, for example).
     *
     * @return list<array{path: string, json: Json}>
     */
    public function getModuleJsonFiles(): array
    {
        $paths = $this->getScanPaths();

        if ($this->moduleJsonFiles !== null && $paths === $this->scannedPaths) {
            return $this->moduleJsonFiles;
        }

        $this->modules = null;

        $files = [];
        $jsons = [];

        foreach ($paths as $path) {
            $manifests = (array) $this->getFiles()->glob("{$path}/module.json");

            foreach ($manifests as $manifest) {
                // Overlapping scan paths can list the same file twice: read it once.
                $jsons[$manifest] ??= Json::make($manifest, $this->getFiles());

                $files[] = ['path' => $manifest, 'json' => $jsons[$manifest]];
            }
        }

        $this->scannedPaths = $paths;

        return $this->moduleJsonFiles = $files;
    }

    /**
     * Get all modules.
     *
     * @return array<string, Module>
     */
    #[\Override]
    public function all(): array
    {
        return $this->scan();
    }

    /**
     * Get all modules as collection instance.
     *
     * @return Collection<string, Module>
     */
    #[\Override]
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
    }

    /**
     * Get modules by status.
     *
     * @param  bool|int  $status
     * @return array<string, Module>
     */
    #[\Override]
    public function getByStatus($status): array
    {
        $modules = [];

        /** @var Module $module */
        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status)) {
                $modules[$name] = $module;
            }
        }

        return $modules;
    }

    /**
     * Determine whether the given module exist.
     *
     * @param  string  $name
     */
    public function has($name): bool
    {
        return $this->find((string) $name) !== null;
    }

    /**
     * Get list of enabled modules.
     *
     * @return array<string, Module>
     */
    #[\Override]
    public function allEnabled(): array
    {
        return $this->getByStatus(true);
    }

    /**
     * Get list of disabled modules.
     *
     * @return array<string, Module>
     */
    #[\Override]
    public function allDisabled(): array
    {
        return $this->getByStatus(false);
    }

    /**
     * Get count from all modules.
     */
    #[\Override]
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Get all ordered modules.
     *
     * @return array<string, Module>
     */
    #[\Override]
    public function getOrdered(string $direction = 'asc'): array
    {
        $modules = $this->allEnabled();

        uasort($modules, function (Module $a, Module $b) use ($direction) {
            if ($a->get('priority') === $b->get('priority')) {
                return 0;
            }

            if ($direction === 'desc') {
                return $a->get('priority') < $b->get('priority') ? 1 : -1;
            }

            return $a->get('priority') > $b->get('priority') ? 1 : -1;
        });

        return $modules;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getPath(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path('Modules'));
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated The package no longer calls this method: enabled modules are registered by the
     *             package service provider through ModuleManifest. Will be removed in v14.
     */
    #[\Override]
    public function register(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated The package no longer calls this method: enabled modules are loaded through
     *             ModuleManifest, and each module boots through its own service providers.
     *             Will be removed in v14.
     */
    #[\Override]
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function find(string $name): ?Module
    {
        $module = $this->findScanned($name);

        // The scan is kept per instance. On a miss, scan again if the scan paths changed
        // since (config set after the scan), so a lookup never pays for the path check.
        if ($module === null && $this->modules !== null && $this->scannedPaths !== $this->getScanPaths()) {
            $this->resetModules();

            $module = $this->findScanned($name);
        }

        return $module;
    }

    /**
     * Find a module in the scanned modules.
     */
    private function findScanned(string $name): ?Module
    {
        $modules = $this->all();

        // An exact (case-insensitive) match wins. Otherwise try the studly name module:make
        // gives a module, so kebab or snake names (blog-post, blog_post) find BlogPost.
        return $modules[strtolower($name)] ?? $modules[strtolower(Str::studly($name))] ?? null;
    }

    /**
     * Find a specific module, if there return that, otherwise throw exception.
     *
     * @throws ModuleNotFoundException
     */
    #[\Override]
    public function findOrFail(string $name): Module
    {
        $module = $this->find($name);

        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$name}] does not exist!");
    }

    /**
     * Get all modules as laravel collection instance.
     *
     * @param  bool|int  $status
     * @return Collection<string, Module>
     */
    public function collections($status = 1): Collection
    {
        return new Collection($this->getByStatus($status));
    }

    /**
     * Get module path for a specific module.
     *
     * @param  string  $module
     */
    #[\Override]
    public function getModulePath($module): string
    {
        try {
            return $this->findOrFail($module)->getPath().'/';
        } catch (ModuleNotFoundException $e) {
            return $this->getPath().'/'.Str::studly($module).'/';
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function assetPath(string $module): string
    {
        return $this->config('paths.assets').'/'.$module;
    }

    /**
     * {@inheritDoc}
     *
     * @param  mixed  $default
     * @return mixed
     */
    #[\Override]
    public function config(string $key, $default = null)
    {
        return $this->config->get('modules.'.$key, $default);
    }

    /**
     * Get storage path for module used.
     */
    public function getUsedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->getFiles()->exists($directory) === false) {
            $this->getFiles()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (! $this->getFiles()->exists($path)) {
            $this->getFiles()->put($path, '');
        }

        return $path;
    }

    /**
     * Set module used for cli session.
     *
     * @param  string  $name
     * @return void
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed($name)
    {
        $module = $this->findOrFail($name);

        $this->getFiles()->put($this->getUsedStoragePath(), $module);

        $module->fireEvent(ModuleEvent::USED);
    }

    /**
     * Forget the module used for cli session.
     *
     * @return void
     */
    public function forgetUsed()
    {
        if ($this->getFiles()->exists($this->getUsedStoragePath())) {
            $this->getFiles()->delete($this->getUsedStoragePath());
        }
    }

    /**
     * Get module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function getUsedNow(): string
    {
        return $this->findOrFail($this->getFiles()->get($this->getUsedStoragePath()));
    }

    /**
     * Get laravel filesystem instance.
     */
    #[\Override]
    public function getFiles(): Filesystem
    {
        return $this->files;
    }

    /**
     * Get module assets path.
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * Get asset url from a specific module.
     *
     * @throws InvalidAssetPath
     */
    public function asset(string $asset): string
    {
        if (Str::contains($asset, ':') === false) {
            throw InvalidAssetPath::missingModuleName($asset);
        }
        [$name, $url] = explode(':', $asset);

        $baseUrl = str_replace(public_path().DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->url->asset($baseUrl."/{$name}/".$url);

        return str_replace(['http://', 'https://'], '//', $url);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function isEnabled(string $name): bool
    {
        return $this->findOrFail($name)->isEnabled();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function isDisabled(string $name): bool
    {
        return ! $this->isEnabled($name);
    }

    /**
     * Enabling a specific module.
     *
     * @return void
     *
     * @throws ModuleNotFoundException
     */
    public function enable(string $name)
    {
        $this->findOrFail($name)->enable();
    }

    /**
     * Disabling a specific module.
     *
     * @return void
     *
     * @throws ModuleNotFoundException
     */
    public function disable(string $name)
    {
        $this->findOrFail($name)->disable();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function delete(string $name): bool
    {
        $deleted = $this->findOrFail($name)->delete();

        $this->resetModules();

        return $deleted;
    }

    /**
     * Update dependencies for the specified module.
     *
     * @return void
     */
    public function update(string $module)
    {
        (new Updater($this))->update($module);
    }

    /**
     * Install the specified module.
     *
     * An empty version installs the Installer default: `dev-master` via composer,
     * `master` via git. The parameter stays a non-nullable string, so subclasses
     * that override this method with the previous signature remain compatible.
     */
    public function install(string $name, string $version = '', string $type = 'composer', bool $subtree = false): Process
    {
        $installer = new Installer($name, $version === '' ? null : $version, $type, $subtree);

        return $installer->setRepository($this)->run();
    }

    /**
     * Get stub path.
     */
    public function getStubPath(): ?string
    {
        if ($this->stubPath !== null) {
            return $this->stubPath;
        }

        if ($this->config('stubs.enabled') === true) {
            return $this->config('stubs.path');
        }

        return $this->stubPath;
    }

    /**
     * Set stub path.
     */
    public function setStubPath(string $stubPath): self
    {
        $this->stubPath = $stubPath;

        return $this;
    }

    /**
     * Forget the scanned modules, so the next call scans the module.json files again.
     */
    public function resetModules(): static
    {
        $this->scannedPaths = [];
        $this->moduleJsonFiles = null;
        $this->modules = null;

        return $this;
    }
}
