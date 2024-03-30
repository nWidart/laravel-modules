<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command as Console;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\FileRepository;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;

class ModuleGenerator extends Generator
{
    /**
     * The module name will created.
     *
     * @var string
     */
    protected $name;

    /**
     * The laravel config instance.
     *
     * @var Config
     */
    protected $config;

    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The laravel console instance.
     *
     * @var Console
     */
    protected $console;

    /**
     * The laravel component Factory instance.
     *
     * @var \Illuminate\Console\View\Components\Factory
     */
    protected $component;

    /**
     * The activator instance
     *
     * @var ActivatorInterface
     */
    protected $activator;

    /**
     * The module instance.
     *
     * @var \Nwidart\Modules\Module
     */
    protected $module;

    /**
     * Force status.
     *
     * @var bool
     */
    protected $force = false;

    /**
     * set default module type.
     *
     * @var string
     */
    protected $type = 'web';

    /**
     * Enables the module.
     *
     * @var bool
     */
    protected $isActive = false;

    /**
     * Module author
     *
     * @var array
     */
    protected array $author = [
        'name', 'email'
    ];

    /**
     * Vendor name
     *
     * @var string
     */
    protected ?string $vendor = null;

    /**
     * The constructor.
     * @param $name
     * @param FileRepository $module
     * @param Config     $config
     * @param Filesystem $filesystem
     * @param Console    $console
     */
    public function __construct(
        $name,
        FileRepository $module = null,
        Config $config = null,
        Filesystem $filesystem = null,
        Console $console = null,
        ActivatorInterface $activator = null
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
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set active flag.
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive(bool $active)
    {
        $this->isActive = $active;

        return $this;
    }

    /**
     * Get the name of module will created. By default in studly case.
     *
     * @return string
     */
    public function getName()
    {
        return Str::studly($this->name);
    }

    /**
     * Get the laravel config instance.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the laravel config instance.
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set the modules activator
     *
     * @param ActivatorInterface $activator
     *
     * @return $this
     */
    public function setActivator(ActivatorInterface $activator)
    {
        $this->activator = $activator;

        return $this;
    }

    /**
     * Get the laravel filesystem instance.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Set the laravel filesystem instance.
     *
     * @param Filesystem $filesystem
     *
     * @return $this
     */
    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get the laravel console instance.
     *
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     *
     * @param Console $console
     *
     * @return $this
     */
    public function setConsole($console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * @return \Illuminate\Console\View\Components\Factory
     */
    public function getComponent(): \Illuminate\Console\View\Components\Factory
    {
        return $this->component;
    }

    /**
     * @param \Illuminate\Console\View\Components\Factory $component
     */
    public function setComponent(\Illuminate\Console\View\Components\Factory $component): self
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get the module instance.
     *
     * @return \Nwidart\Modules\Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the module instance.
     */
    public function setModule($module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Setting the author from the command
     */
    function setAuthor(?string $name = null, ?string $email = null): self
    {
        $this->author['name'] = $name;
        $this->author['email'] = $email;

        return $this;
    }

    /**
     * Installing vendor from the command
     */
    function setVendor(?string $vendor = null): self
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

        if ($this->module->has($name)) {
            if ($this->force) {
                $this->module->delete($name);
            } else {
                $this->component->error("Module [{$name}] already exists!");

                return E_ERROR;
            }
        }
        $this->component->info("Creating module: [$name]");

        $this->generateFolders();

        $this->generateModuleJsonFile();

        if ($this->type !== 'plain') {
            $this->generateFiles();
            $this->generateResources();
        }

        if ($this->type === 'plain') {
            $this->cleanModuleJsonFile();
        }

        $this->activator->setActiveByName($name, $this->isActive);

        $this->console->newLine(1);

        $this->component->info("Module [{$name}] created successfully.");

        return 0;
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

            $path = $this->module->getModulePath($this->getName()) . '/' . $folder->getPath();

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
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Generate the files.
     */
    public function generateFiles()
    {
        foreach ($this->getFiles() as $stub => $file) {
            $path = $this->module->getModulePath($this->getName()) . $file;

            $this->component->task("Generating file {$path}", function () use ($stub, $path) {
                if (!$this->filesystem->isDirectory($dir = dirname($path))) {
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
                'name' => $this->getName() . 'ServiceProvider',
                'module' => $this->getName(),
                '--master' => true,
            ]);
        } else {
            // delete register ServiceProvider on module.json
            $path           = $this->module->getModulePath($this->getName()) . DIRECTORY_SEPARATOR . 'module.json';
            $module_file  =   $this->filesystem->get($path);
            $this->filesystem->put(
                $path,
                preg_replace('/"providers": \[.*?\],/s', '"providers": [ ],', $module_file)
            );
        }

        $routeGeneratorConfig = GenerateConfigReader::read('route-provider');
        if (
            (is_null($routeGeneratorConfig->getPath()) && $providerGenerator->generate())
            || (!is_null($routeGeneratorConfig->getPath()) && $routeGeneratorConfig->generate())
        ) {
            $this->console->call('module:route-provider', [
                'module' => $this->getName(),
            ]);
        } else {
            if ($providerGenerator->generate()) {
                // comment register RouteServiceProvider
                $this->filesystem->replaceInFile(
                    '$this->app->register(Route',
                    '// $this->app->register(Route',
                    $this->module->getModulePath($this->getName()) . DIRECTORY_SEPARATOR . $providerGenerator->getPath() . DIRECTORY_SEPARATOR . sprintf('%sServiceProvider.php', $this->getName())
                );
            }
        }

        if (GenerateConfigReader::read('controller')->generate() === true) {
            $options = $this->type == 'api' ? ['--api' => true] : [];
            $this->console->call('module:make-controller', [
                'controller' => $this->getName() . 'Controller',
                'module' => $this->getName(),
            ] + $options);
        }
    }

    /**
     * Get the contents of the specified stub file by given stub name.
     */
    protected function getStubContents($stub): string
    {
        return (new Stub(
            '/' . $stub . '.stub',
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

        if (!isset($replacements[$stub])) {
            return [];
        }

        $keys = $replacements[$stub];

        $replaces = [];

        if ($stub === 'json' || $stub === 'composer') {
            if (in_array('PROVIDER_NAMESPACE', $keys, true) === false) {
                $keys[] = 'PROVIDER_NAMESPACE';
            }
        }

        foreach ($keys as $key) {
            if (method_exists($this, $method = 'get' . ucfirst(Str::studly(strtolower($key))) . 'Replacement')) {
                $replaces[$key] = $this->$method();
            } else {
                $replaces[$key] = null;
            }
        }

        return $replaces;
    }

    /**
     * Generate the module.json file
     */
    private function generateModuleJsonFile()
    {
        $path = $this->module->getModulePath($this->getName()) . 'module.json';

        $this->component->task("Generating file $path", function () use ($path) {
            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
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
        $path = $this->module->getModulePath($this->getName()) . 'module.json';

        $content = $this->filesystem->get($path);
        $namespace = $this->getModuleNamespaceReplacement();
        $studlyName = $this->getStudlyNameReplacement();
        $class = "{$studlyName}ServiceProvider";

        $provider_namespace = str_replace('\\', '\\\\', config('modules.paths.generator.provider.namespace', $this->getPathNamespace(config('modules.paths.generator.provider.path', 'app/Providers'))));
        $provider = '"' . $namespace . '\\\\' . $studlyName . '\\\\' . $provider_namespace . '\\\\' . $class  . '"';

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
     * Get the module name in studly case.
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getName();
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
        return str_replace('\\', '\\\\', $this->module->config('namespace'));
    }

    /**
     * Get replacement for $CONTROLLER_NAMESPACE$.
     */
    private function getControllerNamespaceReplacement(): string
    {
        return $this->module->config('paths.generator.controller.namespace') ?? $this->getPathNamespace($this->module->config('paths.generator.controller.path', 'app/Http/Controllers'));
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

    protected function getProviderNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GenerateConfigReader::read('provider')->getNamespace());
    }

    public function getPathNamespace(string $path): string
    {
        $path = str_replace('/', '\\', collect(explode('/', $path))->map(fn ($dir) => Str::studly($dir))->implode('/'));

        return $path;
    }
}
