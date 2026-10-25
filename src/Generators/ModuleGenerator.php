<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command as Console;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Nwidart\Modules\Constants\ModuleEvent;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\FileRepository;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\ReplacementKeyCommand;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\PathNamespace;

class ModuleGenerator extends Generator
{
    use PathNamespace;

    /**
     * Generator keys whose paths hold PSR-4 classes under the module namespace.
     *
     * These are the entries in the "app/" and "app/Http/" sections of config/config.php.
     * The module composer.json maps the module namespace to paths.app_folder, so these
     * paths must sit inside it. The other generator paths (config, database, lang,
     * resources, routes, tests) are not checked: they hold no classes, or have their
     * own PSR-4 entries in the composer.json stub (database/factories,
     * database/seeders, tests).
     */
    private const APP_FOLDER_GENERATOR_KEYS = [
        // app/
        'actions',
        'casts',
        'channels',
        'class',
        'command',
        'command_replacements',
        'component-class',
        'emails',
        'event',
        'enums',
        'exceptions',
        'jobs',
        'helpers',
        'interfaces',
        'listener',
        'model',
        'notifications',
        'observer',
        'policies',
        'provider',
        'repository',
        'resource',
        'route-provider',
        'rules',
        'services',
        'scopes',
        'traits',

        // app/Http/
        'controller',
        'filter',
        'request',
    ];

    /**
     * The module name will created.
     */
    protected ?string $name = null;

    /**
     * The laravel config instance.
     */
    protected ?Config $config = null;

    /**
     * The laravel filesystem instance.
     */
    protected ?Filesystem $filesystem = null;

    /**
     * The laravel console instance.
     */
    protected ?Console $console = null;

    /**
     * The laravel component Factory instance.
     */
    protected ?Factory $component = null;

    /**
     * The activator instance
     */
    protected ?ActivatorInterface $activator = null;

    /**
     * The module instance.
     */
    protected mixed $module = null;

    /**
     * Force status.
     */
    protected bool $force = false;

    /**
     * set default module type.
     */
    protected string $type = 'web';

    /**
     * Whether to generate an Inertia module.
     */
    protected bool $inertia = false;

    /**
     * Enables the module.
     */
    protected bool $isActive = false;

    /**
     * Module author
     */
    protected array $author = [
        'name', 'email',
    ];

    /**
     * Vendor name
     */
    protected ?string $vendor = null;

    /**
     * The constructor.
     */
    public function __construct(
        $name,
        ?FileRepository $module = null,
        ?Config $config = null,
        ?Filesystem $filesystem = null,
        ?Console $console = null,
        ?ActivatorInterface $activator = null
    ) {
        $this->name = $name;
        $this->config = $config;
        $this->filesystem = $filesystem;
        $this->console = $console;
        $this->module = $module;
        $this->activator = $activator;
    }

    /**
     * Set type.
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set whether to generate an Inertia module.
     */
    public function setInertia(bool $inertia): self
    {
        $this->inertia = $inertia;

        return $this;
    }

    /**
     * Set active flag.
     */
    public function setActive(bool $active): self
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * Get the name of module that will be created (in StudlyCase).
     */
    public function getName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get the laravel config instance.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Set the laravel config instance.
     */
    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set the modules activator
     */
    public function setActivator(ActivatorInterface $activator): self
    {
        $this->activator = $activator;

        return $this;
    }

    /**
     * Get the laravel filesystem instance.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set the laravel filesystem instance.
     */
    public function setFilesystem(Filesystem $filesystem): self
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get the laravel console instance.
     */
    public function getConsole(): Console
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     */
    public function setConsole(Console $console): self
    {
        $this->console = $console;

        return $this;
    }

    public function getComponent(): Factory
    {
        return $this->component;
    }

    public function setComponent(Factory $component): self
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the module instance.
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Set the module instance.
     */
    public function setModule(mixed $module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Setting the author from the command
     */
    public function setAuthor(?string $name = null, ?string $email = null): self
    {
        $this->author['name'] = $name;
        $this->author['email'] = $email;

        return $this;
    }

    /**
     * Installing vendor from the command
     */
    public function setVendor(?string $vendor = null): self
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * Get the list of folders will created.
     */
    public function getFolders(): array
    {
        return $this->module->config('paths.generator');
    }

    /**
     * Get the list of files will created.
     */
    public function getFiles(): array
    {
        return $this->module->config('stubs.files');
    }

    /**
     * Set force status.
     */
    public function setForce(bool|int $force): self
    {
        $this->force = $force;

        return $this;
    }

    /**
     * Generate the module.
     */
    public function generate(): int
    {
        $name = $this->getName();

        if (! $this->validateGeneratorPaths()) {
            return E_ERROR;
        }

        if ($this->module->has($name)) {
            if ($this->force) {
                $this->module->delete($name);
            } else {
                $this->component->error("Module [{$name}] already exists!");

                return E_ERROR;
            }
        }

        Event::dispatch(sprintf('modules.%s.%s', strtolower($name), ModuleEvent::CREATING));

        $this->component->info("Creating module: [$name]");

        $this->generateFolders();

        $this->generateModuleJsonFile();

        if ($this->type !== 'plain') {
            $this->generateFiles();
            $this->module->resetModules();
            $this->generateResources();
        }

        if ($this->type === 'plain') {
            $this->cleanModuleJsonFile();
            $this->module->resetModules();
        }

        $this->activator->setActiveByName($name, $this->isActive);

        $this->console->newLine(1);

        $this->component->info("Module [{$name}] created successfully.");

        $this->fireEvent(ModuleEvent::CREATED);

        return 0;
    }

    /**
     * Check that every class generator path sits inside paths.app_folder.
     *
     * The package composer.json stub maps the module namespace to the app folder, so a
     * class generated outside it cannot be autoloaded. Fails before anything is written.
     * Skipped when no composer.json is generated, or when the composer stub in use (for
     * example an older published one) does not map anything to the app folder.
     */
    protected function validateGeneratorPaths(): bool
    {
        $appFolderConfig = (string) $this->module->config('paths.app_folder');
        $appFolder = trim($appFolderConfig, '/');

        // An empty app folder maps the module namespace to the module root.
        if ($appFolder === '' || ! $this->composerJsonUsesAppFolder()) {
            return true;
        }

        $outside = [];

        foreach (self::APP_FOLDER_GENERATOR_KEYS as $key) {
            $config = $this->module->config("paths.generator.$key");
            $path = is_array($config) ? ($config['path'] ?? null) : $config;

            if (! is_string($path) || trim($path, '/') === '') {
                continue;
            }

            $path = trim($path, '/');

            if ($path !== $appFolder && ! str_starts_with($path, $appFolder.'/')) {
                $outside[] = "$key ($path)";
            }
        }

        if ($outside === []) {
            return true;
        }

        $namespace = ($this->module->config('namespace') ?? $this->path_namespace($this->module->config('paths.modules')))
            .'\\'.$this->getStudlyNameReplacement().'\\';

        $this->component->error(sprintf(
            'Generator paths must be inside paths.app_folder [%s], because the module composer.json maps %s to it. Move these paths under %s/ in config/modules.php:',
            $appFolderConfig,
            $namespace,
            $appFolder,
        ));
        $this->component->bulletList($outside);

        return false;
    }

    /**
     * Whether the generated composer.json maps a namespace to $APP_FOLDER_NAME$.
     *
     * Reads the composer stub the same way generateFiles() resolves it.
     */
    private function composerJsonUsesAppFolder(): bool
    {
        if ($this->type === 'plain' || ! array_key_exists('composer', (array) $this->getFiles())) {
            return false;
        }

        return str_contains((new Stub('/composer.stub'))->getContents(), '$APP_FOLDER_NAME$');
    }

    /**
     * Generate the folders.
     */
    public function generateFolders()
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GenerateConfigReader::read($key);

            if ($folder->generate() === false) {
                continue;
            }

            $path = $this->module->getModulePath($this->getName()).'/'.$folder->getPath();

            $this->filesystem->ensureDirectoryExists($path, 0755, true);
            if (config('modules.stubs.gitkeep')) {
                $this->generateGitKeep($path);
            }
        }
    }

    /**
     * Generate git keep to the specified path.
     */
    public function generateGitKeep(string $path)
    {
        $this->filesystem->put($path.'/.gitkeep', '');
    }

    /**
     * Generate the files.
     */
    public function generateFiles()
    {
        $bladeViewStubs = ['views/index', 'views/master'];

        foreach ($this->getFiles() as $stub => $file) {
            if ($this->inertia && in_array($stub, $bladeViewStubs, true)) {
                continue;
            }

            $path = $this->module->getModulePath($this->getName()).$file;

            $this->component->task("Generating file {$path}", function () use ($stub, $path) {
                if (! $this->filesystem->isDirectory($dir = dirname($path))) {
                    $this->filesystem->makeDirectory($dir, 0775, true);
                }

                $this->filesystem->put($path, $this->getStubContents($stub));
            });
        }
    }

    /**
     * Generate some resources.
     */
    public function generateResources()
    {
        if (GenerateConfigReader::read('seeder')->generate() === true) {
            $this->console->call('module:make-seed', [
                'name' => $this->getName(),
                'module' => $this->getName(),
                '--master' => true,
            ]);
        }

        $providerGenerator = GenerateConfigReader::read('provider');
        if ($providerGenerator->generate() === true) {
            $this->console->call('module:make-provider', [
                'name' => $this->getName().'ServiceProvider',
                'module' => $this->getName(),
                '--master' => true,
            ]);
        } else {
            // delete register ServiceProvider on module.json
            $path = $this->module->getModulePath($this->getName()).DIRECTORY_SEPARATOR.'module.json';
            $module_file = $this->filesystem->get($path);
            $this->filesystem->put(
                $path,
                preg_replace('/"providers": \[.*?\],/s', '"providers": [ ],', $module_file)
            );
        }

        if (GenerateConfigReader::read('event-provider')->generatesWith($providerGenerator->generate())) {
            $this->console->call('module:make-event-provider', [
                'module' => $this->getName(),
            ]);
        }

        if (GenerateConfigReader::read('route-provider')->generatesWith($providerGenerator->generate())) {
            $this->console->call('module:route-provider', [
                'module' => $this->getName(),
            ]);
        }

        if (GenerateConfigReader::read('controller')->generate() === true) {
            $options = $this->type == 'api' ? ['--api' => true] : [];
            if ($this->inertia) {
                $options = ['--inertia' => true];
            }
            $this->console->call('module:make-controller', [
                'controller' => $this->getName().'Controller',
                'module' => $this->getName(),
            ] + $options);
        }

        if ($this->inertia) {
            foreach (['Index', 'Create', 'Show', 'Edit'] as $page) {
                $this->console->call('module:make-inertia-page', [
                    'name' => $page,
                    'module' => $this->getName(),
                ]);
            }
        }
    }

    /**
     * Get the contents of the specified stub file by given stub name.
     */
    protected function getStubContents($stub): string
    {
        return (new Stub(
            '/'.$stub.'.stub',
            $this->getReplacement($stub)
        )
        )->render();
    }

    /**
     * get the list for the replacements.
     */
    public function getReplacements()
    {
        return $this->module->config('stubs.replacements');
    }

    /**
     * Get array replacement for the specified stub.
     */
    protected function getReplacement($stub): array
    {
        $replacements = $this->module->config('stubs.replacements');

        // Temporarily check if the replacements are defined; remove in the next major version.
        if (! isset($replacements['composer']['APP_FOLDER_NAME'])) {
            $replacements['composer'][] = 'APP_FOLDER_NAME';
        }
        if (! isset($replacements['routes/web']['PLURAL_LOWER_NAME'])) {
            $replacements['routes/web'][] = 'PLURAL_LOWER_NAME';
        }
        if (! isset($replacements['routes/api']['PLURAL_LOWER_NAME'])) {
            $replacements['routes/api'][] = 'PLURAL_LOWER_NAME';
        }

        if (! isset($replacements[$stub])) {
            return [];
        }

        $keys = $replacements[$stub];

        $replaces = [];

        if ($stub === 'json' || $stub === 'composer') {
            if (in_array('PROVIDER_NAMESPACE', $keys, true) === false) {
                $keys[] = 'PROVIDER_NAMESPACE';
            }
        }

        foreach ($keys as $key => $value) {
            if (class_exists($value) && is_subclass_of($value, ReplacementKeyCommand::class)) {
                $replaces[strtoupper($key)] = (new $value($this))->handle();
            } elseif (method_exists($this, $method = 'get'.ucfirst(Str::studly(strtolower($value))).'Replacement')) {
                $replace = $this->$method();

                if ($stub === 'routes/web' || $stub === 'routes/api') {
                    $replace = str_replace('\\\\', '\\', $replace);
                }

                $replaces[$value] = $replace;
            } else {
                $replaces[$value] = null;
            }
        }

        return $replaces;
    }

    /**
     * Generate the module.json file
     */
    private function generateModuleJsonFile()
    {
        $path = $this->module->getModulePath($this->getName()).'module.json';

        $this->component->task("Generating file $path", function () use ($path) {
            if (! $this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $this->getStubContents('json'));
        });
    }

    /**
     * Remove the default service provider that was added in the module.json file
     * This is needed when a --plain module was created
     */
    private function cleanModuleJsonFile()
    {
        $path = $this->module->getModulePath($this->getName()).'module.json';

        $content = $this->filesystem->get($path);
        $namespace = $this->getModuleNamespaceReplacement();
        $studlyName = $this->getStudlyNameReplacement();

        // Same value as the json stub: $MODULE_NAMESPACE$\\$STUDLY_NAME$\\$PROVIDER_NAMESPACE$\\$STUDLY_NAME$ServiceProvider
        $providerNamespace = $this->getProviderNamespaceReplacement();

        $provider = '"'.$namespace.'\\\\'.$studlyName.'\\\\'.$providerNamespace.'\\\\'.$studlyName.'ServiceProvider"';

        $content = str_replace($provider, '', $content);

        $this->filesystem->put($path, $content);
    }

    /**
     * Get the module name in lower case.
     */
    protected function getLowerNameReplacement(): string
    {
        return strtolower($this->getName());
    }

    /**
     * Get the module name in lowercase plural form.
     */
    protected function getPluralLowerNameReplacement(): string
    {
        return Str::of($this->getName())->lower()->plural();
    }

    protected function getKebabNameReplacement(): string
    {
        return Str::kebab($this->getName());
    }

    /**
     * Get the module name in studly case.
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getName();
    }

    /**
     * Get the module name in plural studly case.
     */
    protected function getPluralStudlyNameReplacement(): string
    {
        return Str::of($this->getName())->pluralStudly();
    }

    /**
     * Get the module name in plural upper case.
     */
    protected function getPluralUpperNameReplacement(): string
    {
        return Str::of($this->getName())->upper()->plural();
    }

    /**
     * Get replacement for $VENDOR$.
     */
    protected function getVendorReplacement(): string
    {
        return $this->vendor ?: $this->module->config('composer.vendor');
    }

    /**
     * Get replacement for $MODULE_NAMESPACE$.
     */
    protected function getModuleNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', $this->module->config('namespace') ?? $this->path_namespace($this->module->config('paths.modules')));
    }

    /**
     * Get replacement for $CONTROLLER_NAMESPACE$.
     */
    private function getControllerNamespaceReplacement(): string
    {
        if ($this->module->config('paths.generator.controller.namespace')) {
            return $this->module->config('paths.generator.controller.namespace');
        } else {
            return $this->path_namespace($this->strip_app_folder($this->module->config('paths.generator.controller.path', 'app/Http/Controllers')));
        }
    }

    /**
     * Get replacement for $AUTHOR_NAME$.
     */
    protected function getAuthorNameReplacement(): string
    {
        return $this->author['name'] ?: $this->module->config('composer.author.name');
    }

    /**
     * Get replacement for $AUTHOR_EMAIL$.
     */
    protected function getAuthorEmailReplacement(): string
    {
        return $this->author['email'] ?: $this->module->config('composer.author.email');
    }

    /**
     * Get replacement for $APP_FOLDER_NAME$.
     */
    protected function getAppFolderNameReplacement(): string
    {
        return $this->module->config('paths.app_folder');
    }

    protected function getProviderNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GenerateConfigReader::read('provider')->getNamespace());
    }

    /**
     * Fire the module event.
     *
     * Modules are keyed by their studly name, so look up getName(), not the
     * raw input. The module was just written to disk; if the repository still
     * cannot find it, fail with a clear error instead of a null dereference.
     *
     * @throws ModuleNotFoundException
     */
    protected function fireEvent(string $event): void
    {
        $this->module->findOrFail($this->getName())->fireEvent($event);
    }
}
