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
     * paths must sit inside it. With an empty app folder, each path must match its
     * namespace instead. The other generator paths (config, database, lang, resources,
     * routes, tests) are not checked: they hold no classes, or have their own PSR-4
     * entries in the composer.json stub (database/factories, database/seeders, tests).
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
        'job-middleware',
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
     * The generator key that owns each default stub in modules.stubs.files.
     *
     * A stub is not written when its owner has "generate" set to false. vite and package
     * belong to assets: the vite.config.js stub builds resources/assets/{js,sass}, and
     * package.json only exists for that build. Stubs not listed here (composer, custom
     * stubs) are always written. Keys are matched by stub name, not by file path, so a
     * changed generator path does not change which stubs it owns.
     */
    private const STUB_GENERATOR_KEYS = [
        'routes/web' => 'routes',
        'routes/api' => 'routes',
        'views/index' => 'views',
        'views/master' => 'views',
        'scaffold/config' => 'config',
        'assets/js/app' => 'assets',
        'assets/sass/app' => 'assets',
        'vite' => 'assets',
        'package' => 'assets',
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
     *
     * @var array<array-key, string|null> The 'name' and 'email' set by setAuthor().
     */
    protected array $author = [
        'name', 'email',
    ];

    /**
     * Vendor name
     */
    protected ?string $vendor = null;

    /**
     * Stub contents rendered by generate() before anything is written, keyed by stub name.
     *
     * @var array<string, string>
     */
    protected array $renderedStubs = [];

    /**
     * The constructor.
     *
     * @param  string|null  $name
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
     *
     * @return array<string, mixed> The modules.paths.generator config, keyed by generator key.
     */
    public function getFolders(): array
    {
        return $this->module->config('paths.generator');
    }

    /**
     * Get the list of files will created.
     *
     * @return array<string, string> The modules.stubs.files config: stub name => file path.
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

        $exists = $this->module->has($name);

        if ($exists && ! $this->force) {
            $this->component->error("Module [{$name}] already exists!");

            return E_ERROR;
        }

        // Render every stub up front: a stub that renders invalid JSON stops here,
        // before the existing module is deleted or anything is written.
        if (! $this->renderStubs($exists)) {
            return E_ERROR;
        }

        if ($exists) {
            $this->module->delete($name);
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
     * Check that every class generator path can be autoloaded by the module composer.json.
     *
     * The package composer.json stub maps the module namespace to paths.app_folder:
     * - With an app folder, every class generator path must sit inside it.
     * - With an empty app folder, the namespace maps to the module root, so every class
     *   generator path must equal the namespace derived from it: each segment must be
     *   StudlyCase already. "Providers" passes; "app/Providers" (namespace App\Providers) fails.
     *
     * Fails before anything is written. Skipped when no composer.json is generated, or when
     * the composer stub in use (for example an older published one) does not map anything
     * to the app folder.
     */
    protected function validateGeneratorPaths(): bool
    {
        if (! $this->composerJsonUsesAppFolder()) {
            return true;
        }

        $appFolderConfig = (string) $this->module->config('paths.app_folder');
        $appFolder = trim($appFolderConfig, '/');

        if ($appFolder === '') {
            return $this->validateGeneratorPathsAtModuleRoot();
        }

        $outside = [];

        foreach ($this->classGeneratorPaths() as $key => $path) {
            if ($path !== $appFolder && ! str_starts_with($path, $appFolder.'/')) {
                $outside[] = "$key ($path)";
            }
        }

        if ($outside === []) {
            return true;
        }

        $namespace = $this->getModuleNamespaceReplacement().'\\'.$this->getStudlyNameReplacement().'\\';

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
     * With an empty app folder, check that every class generator path equals its namespace.
     *
     * Generators derive the namespace from the path with path_namespace(), which
     * StudlyCases each segment. A segment that is not StudlyCase already ("app" in
     * "app/Providers") gives a namespace that PSR-4 cannot find at that path.
     */
    private function validateGeneratorPathsAtModuleRoot(): bool
    {
        $mismatched = [];

        foreach ($this->classGeneratorPaths() as $key => $path) {
            if ($this->path_namespace($path) !== str_replace('/', '\\', $this->clean_path($path))) {
                $mismatched[] = "$key ($path)";
            }
        }

        if ($mismatched === []) {
            return true;
        }

        $namespace = $this->getModuleNamespaceReplacement().'\\'.$this->getStudlyNameReplacement().'\\';

        $this->component->error(sprintf(
            'Generator paths must match their namespaces, because paths.app_folder is empty and the module composer.json maps %s to the module root. In config/modules.php, drop the app/ prefix and use StudlyCase folders for these paths (for example app/Providers to Providers):',
            $namespace,
        ));
        $this->component->bulletList($mismatched);

        return false;
    }

    /**
     * The configured paths of the class generators, keyed by generator key.
     *
     * Leading and trailing slashes are trimmed. Keys without a path are left out.
     *
     * @return array<string, string>
     */
    private function classGeneratorPaths(): array
    {
        $paths = [];

        foreach (self::APP_FOLDER_GENERATOR_KEYS as $key) {
            $config = $this->module->config("paths.generator.$key");
            $path = is_array($config) ? ($config['path'] ?? null) : $config;

            if (is_string($path) && trim($path, '/') !== '') {
                $paths[$key] = trim($path, '/');
            }
        }

        return $paths;
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
     *
     * @return void
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
     *
     * @return void
     */
    public function generateGitKeep(string $path)
    {
        $this->filesystem->put($path.'/.gitkeep', '');
    }

    /**
     * Generate the files.
     *
     * @return void
     */
    public function generateFiles()
    {
        foreach ($this->getFilesToGenerate() as $stub => $file) {
            $path = $this->module->getModulePath($this->getName()).$file;

            // Render before the task so stub warnings do not break the task line.
            $contents = $this->renderedStubs[$stub] ?? $this->getStubContents($stub);

            $this->component->task("Generating file {$path}", function () use ($contents, $path) {
                if (! $this->filesystem->isDirectory($dir = dirname($path))) {
                    $this->filesystem->makeDirectory($dir, 0775, true);
                }

                $this->filesystem->put($path, $contents);
            });
        }
    }

    /**
     * Generate some resources.
     *
     * @return void
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
            // No service provider is generated, so module.json must not register one.
            $this->clearModuleJsonProviders();
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
     *
     * @param  string  $stub
     */
    protected function getStubContents($stub): string
    {
        $contents = (new Stub(
            '/'.$stub.'.stub',
            $this->getReplacement($stub)
        )
        )->render();

        if (preg_match_all('/\$[A-Z_]+\$/', $contents, $matches) > 0) {
            $this->writeWarning(sprintf(
                'Stub [%s] still contains unreplaced tokens: %s. List them in [modules.stubs.replacements.%s].',
                $stub,
                implode(', ', array_unique($matches[0])),
                $stub
            ));
        }

        return $contents;
    }

    /**
     * Get the stub files generateFiles() writes, keyed by stub name.
     *
     * Inertia modules get no Blade views. Stubs whose generator key has "generate"
     * set to false are skipped, see STUB_GENERATOR_KEYS.
     *
     * @return array<string, string>
     */
    protected function getFilesToGenerate(): array
    {
        $files = $this->getFiles();

        if ($this->inertia) {
            unset($files['views/index'], $files['views/master']);
        }

        return array_filter(
            $files,
            fn (string $stub) => static::stubIsGenerated($stub),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Whether module:make writes the given stub, according to its generator key.
     *
     * A stub without an owner in STUB_GENERATOR_KEYS is always written. So is a stub
     * whose owner is missing from paths.generator (a config published before the key
     * existed), as before the flag was checked.
     */
    public static function stubIsGenerated(string $stub): bool
    {
        $key = self::STUB_GENERATOR_KEYS[$stub] ?? null;

        if ($key === null || config("modules.paths.generator.$key") === null) {
            return true;
        }

        return GenerateConfigReader::read($key)->generate();
    }

    /**
     * Render every stub this module type writes and keep the contents for writing.
     *
     * Returns false, after printing an error per stub, when a JSON target renders
     * invalid JSON. Nothing is written in that case. $overwriting tells the error
     * that an existing module (--force) is kept as it is.
     */
    protected function renderStubs(bool $overwriting = false): bool
    {
        $targets = ['json' => 'module.json'];

        if ($this->type !== 'plain') {
            $targets += $this->getFilesToGenerate();
        }

        $this->renderedStubs = [];
        $valid = true;

        foreach ($targets as $stub => $file) {
            $contents = $this->getStubContents($stub);

            if ($this->rendersJson($stub) && ! json_validate($contents)) {
                $this->component?->error(sprintf(
                    'Stub [%s] renders invalid JSON for [%s]: %s. Values returned by a %s are inserted as-is, so in JSON stubs they must be JSON-escaped. Module [%s] was not %s.',
                    $stub,
                    ltrim($file, '/'),
                    json_last_error_msg(),
                    class_basename(ReplacementKeyCommand::class),
                    $this->getName(),
                    $overwriting ? 'overwritten' : 'created'
                ));

                $valid = false;
            }

            $this->renderedStubs[$stub] = $contents;
        }

        return $valid;
    }

    /**
     * get the list for the replacements.
     *
     * @return array<string, array<array-key, string>>|null The modules.stubs.replacements config: stub name => replacement keys.
     */
    public function getReplacements()
    {
        return $this->module->config('stubs.replacements');
    }

    /**
     * Get array replacement for the specified stub.
     *
     * @param  string  $stub
     * @return array<string, string|null>
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

        $escapeForJson = $this->rendersJson($stub);

        foreach ($keys as $key => $value) {
            if (class_exists($value) && is_subclass_of($value, ReplacementKeyCommand::class)) {
                // Custom commands own their output and are inserted as returned.
                $replaces[strtoupper($key)] = (new $value($this))->handle();
            } elseif (method_exists($this, $method = 'get'.ucfirst(Str::studly(strtolower($value))).'Replacement')) {
                $replace = (string) $this->$method();

                $replaces[$value] = $escapeForJson ? $this->escapeJsonString($replace) : $replace;
            } else {
                $this->writeWarning(is_string($key)
                    ? sprintf('Replacement [%s] for stub [%s] maps to [%s], which is not a %s class. It was left empty.', $key, $stub, $value, class_basename(ReplacementKeyCommand::class))
                    : sprintf('Unknown replacement key [%s] for stub [%s]. It was replaced with an empty string.', $value, $stub)
                );

                $replaces[$value] = null;
            }
        }

        return $replaces;
    }

    /**
     * Whether the given stub renders a JSON file.
     */
    protected function rendersJson(string $stub): bool
    {
        if ($stub === 'json') {
            return true;
        }

        $file = ($this->module->config('stubs.files') ?? [])[$stub] ?? null;

        return is_string($file) && str_ends_with(strtolower($file), '.json');
    }

    /**
     * Escape a value for use inside a JSON string literal.
     */
    protected function escapeJsonString(string $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return $encoded === false ? $value : substr($encoded, 1, -1);
    }

    /**
     * Write a console warning, when a console component is available.
     */
    protected function writeWarning(string $message): void
    {
        $this->component?->warn($message);
    }

    /**
     * Generate the module.json file
     */
    private function generateModuleJsonFile(): void
    {
        $path = $this->module->getModulePath($this->getName()).'module.json';

        // Render before the task so stub warnings do not break the task line.
        $contents = $this->renderedStubs['json'] ?? $this->getStubContents('json');

        $this->component->task("Generating file $path", function () use ($contents, $path) {
            if (! $this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $contents);
        });
    }

    /**
     * Remove the default service provider that was added in the module.json file
     * This is needed when a --plain module was created
     */
    private function cleanModuleJsonFile(): void
    {
        $studlyName = $this->getStudlyNameReplacement();

        $defaultProvider = implode('\\', [
            $this->getModuleNamespaceReplacement(),
            $studlyName,
            $this->getProviderNamespaceReplacement(),
            $studlyName.'ServiceProvider',
        ]);

        $this->filterModuleJsonProviders(fn ($provider) => $provider !== $defaultProvider);
    }

    /**
     * Empty the "providers" list of the generated module.json file.
     */
    private function clearModuleJsonProviders(): void
    {
        $this->filterModuleJsonProviders(fn () => false);
    }

    /**
     * Keep only the module.json providers that pass the given filter.
     *
     * The file is decoded as objects (not arrays) so empty objects stay `{}`,
     * and re-encoded in the stub's format: 4-space indent, unescaped slashes.
     */
    private function filterModuleJsonProviders(callable $keep): void
    {
        $path = $this->module->getModulePath($this->getName()).'module.json';

        $content = $this->filesystem->get($path);
        $json = json_decode($content);

        if (! $json instanceof \stdClass) {
            $this->writeWarning(sprintf('Could not remove the service providers from [%s]: %s.', $path, json_last_error_msg()));

            return;
        }

        if (! property_exists($json, 'providers') || ! is_array($json->providers)) {
            return;
        }

        $providers = array_values(array_filter($json->providers, $keep));

        if ($providers === $json->providers) {
            return;
        }

        $json->providers = $providers;

        $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->filesystem->put($path, $encoded.(str_ends_with($content, "\n") ? "\n" : ''));
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
        return $this->module->config('namespace') ?? $this->path_namespace($this->module->config('paths.modules'));
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
        return GenerateConfigReader::read('provider')->getNamespace();
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
