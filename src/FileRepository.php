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
use Nwidart\Modules\Exceptions\InvalidJsonException;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Process\Installer;
use Nwidart\Modules\Process\Updater;
use Nwidart\Modules\Support\BrokenModule;
use Nwidart\Modules\Support\GeneratorRun;
use Symfony\Component\Process\Process;

abstract class FileRepository implements Countable, RepositoryInterface
{
    use Macroable;

    /**
     * Application instance. The constructor accepts any Container for
     * backwards compatibility, but the repository calls application methods on it.
     * The Lumen application type is kept for the deprecated Lumen support, which will be removed in v14.
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
     *
     * @deprecated The package does not read this value: generators read their stubs from
     *             Stub::setBasePath(), set from the modules.stubs.path config. Will be removed in v14.
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
     * The modules skipped because they are broken, keyed by name, with the reason.
     *
     * @var array<string, string>
     */
    private array $brokenModules = [];

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
                if (! array_key_exists($manifest, $jsons)) {
                    $jsons[$manifest] = $this->readModuleJson($manifest);
                }

                // A broken module.json is skipped when 'skip_broken_modules' is true.
                if ($jsons[$manifest] !== null) {
                    $files[] = ['path' => $manifest, 'json' => $jsons[$manifest]];
                }
            }
        }

        $this->scannedPaths = $paths;

        return $this->moduleJsonFiles = $files;
    }

    /**
     * Decode a module.json file.
     *
     * When 'skip_broken_modules' is true, a file that is not valid JSON is recorded as a broken
     * module under its folder name and null is returned. Otherwise the exception is thrown.
     *
     * @throws InvalidJsonException
     */
    private function readModuleJson(string $manifest): ?Json
    {
        try {
            return Json::make($manifest, $this->getFiles());
        } catch (InvalidJsonException $exception) {
            if (! $this->config('skip_broken_modules', false)) {
                throw $exception;
            }

            // The file cannot be read, so the module has no name: use its folder name.
            $this->markModuleBroken(basename(dirname($manifest)), BrokenModule::invalidJsonReason($manifest, $exception));

            return null;
        }
    }

    /**
     * Get the modules skipped because they are broken, with the reason for each.
     *
     * Filled only when the 'skip_broken_modules' config is true. A module whose module.json
     * is not valid JSON is listed under its folder name; an enabled module with a provider
     * class that does not exist is listed under its name. The application runs without these
     * modules, so the list keeps every module skipped since this repository was created.
     *
     * @return array<string, string> The reason, keyed by module name.
     */
    public function getBrokenModules(): array
    {
        return $this->brokenModules;
    }

    /**
     * Record a module as broken and log the reason, once per module and reason.
     *
     * @internal Called by the package when 'skip_broken_modules' is true.
     */
    public function markModuleBroken(string $name, string $reason): void
    {
        if (($this->brokenModules[$name] ?? null) === $reason) {
            return;
        }

        $this->brokenModules[$name] = $reason;

        BrokenModule::log($name, $reason);
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
        if (($planned = $this->findPlanned($name)) !== null) {
            return $planned;
        }

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
     * Find a module that a module:make dry run creates, but did not write to disk.
     *
     * The generators module:make calls look their module up by name; in a dry run
     * Support\GeneratorRun holds its name and path instead of the disk.
     */
    private function findPlanned(string $name): ?Module
    {
        $planned = GeneratorRun::current()?->plannedModule($name);

        return $planned === null ? null : $this->createModule($this->app, $planned[0], $planned[1]);
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
     * Fires the unused event on the module that was used. An empty file or the name of a
     * module that no longer exists fires no event.
     *
     * @return void
     */
    public function forgetUsed()
    {
        $path = $this->getUsedStoragePath();

        $module = $this->find($this->getFiles()->get($path));

        $this->getFiles()->delete($path);

        $module?->fireEvent(ModuleEvent::UNUSED);
    }

    /**
     * Get module used for cli session.
     *
     * @throws ModuleNotFoundException
     */
    public function getUsedNow(): string
    {
        return $this->findOrFail($this->getFiles()->get($this->getUsedStoragePath()))->getStudlyName();
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
        [$name, $url] = explode(':', $asset, 2);

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
     *
     * @deprecated The package does not read this value: generators read their stubs from
     *             Stub::getBasePath(), set from the modules.stubs.path config. Will be removed in v14.
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
     *
     * @deprecated The package does not read this value, so it does not change the stubs the
     *             generators use. Use Stub::setBasePath() or the modules.stubs.path config.
     *             Will be removed in v14.
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
