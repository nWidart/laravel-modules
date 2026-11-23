<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Exceptions\FileAlreadyExistException;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Generators\FileGenerator;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\GeneratorRun;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Nwidart\Modules\Traits\PathNamespace;
use Nwidart\Modules\Traits\PlansGeneratorRun;
use Symfony\Component\Console\Input\InputOption;

/**
 * @method string getModuleName() Provided by Traits\ModuleCommandTrait.
 */
abstract class GeneratorCommand extends Command
{
    use InteractsWithModuleRepository;
    use PathNamespace;
    use PlansGeneratorRun;

    /**
     * The name of 'name' argument.
     *
     * @var string
     */
    protected $argumentName = '';

    /**
     * Get template contents.
     *
     * @return string
     */
    abstract protected function getTemplateContents();

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getDestinationFilePath();

    /**
     * Create a new generator command instance.
     *
     * Every generator accepts --force, --dry-run and --json. Commands that declare
     * their own option keep it; -f is added only when no other option uses it.
     */
    public function __construct()
    {
        parent::__construct();

        $definition = $this->getDefinition();

        if (! $definition->hasOption('force')) {
            $this->addOption(
                'force',
                $definition->hasShortcut('f') ? null : 'f',
                InputOption::VALUE_NONE,
                'Overwrite the file if it already exists',
            );
        }

        $this->addGeneratorRunOptions();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (($error = $this->validateNameArgument()) !== null) {
            $this->reportError($error);

            return E_ERROR;
        }

        if (($error = $this->validateModuleArgument()) !== null) {
            $this->reportError($error);

            return E_ERROR;
        }

        $path = str_replace('\\', '/', $this->getDestinationFilePath());

        $files = $this->laravel->make(Filesystem::class);

        // A dry run writes nothing, not even the folder.
        if (! GeneratorRun::current()?->isDryRun() && ! $files->isDirectory($dir = dirname($path))) {
            $files->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getTemplateContents();

        try {
            $this->components->task("Generating file {$path}", function () use ($path, $contents) {
                $overwriteFile = $this->hasOption('force') ? $this->option('force') : false;
                (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();
            });
        } catch (FileAlreadyExistException $e) {
            $this->reportError("File : {$path} already exists.");

            return E_ERROR;
        }

        return 0;
    }

    /**
     * Reject an empty name, or a name that would write outside the module: an
     * absolute path or a path with a ".." segment, also once whitespace, hyphens
     * and underscores are removed from the segment. Returns the error message,
     * or null when valid.
     */
    protected function validateNameArgument(): ?string
    {
        if ($this->argumentName === '' || ! $this->hasArgument($this->argumentName)) {
            return null;
        }

        $name = $this->argument($this->argumentName);

        if (! is_string($name)) {
            return null;
        }

        if (trim($name) === '') {
            return "The {$this->argumentName} argument must not be empty.";
        }

        $normalized = str_replace('\\', '/', $name);

        $isAbsolute = str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized) === 1;

        // Generators pass the name through Str::studly(), which removes whitespace, hyphens
        // and underscores, so a segment such as ". ." or "._." becomes ".." in the path.
        $hasParentSegment = collect(explode('/', $normalized))
            ->contains(fn (string $segment) => preg_replace('/[\s\-_]+/u', '', $segment) === '..');

        if ($isAbsolute || $hasParentSegment) {
            return "The {$this->argumentName} [{$name}] must not be an absolute path or contain \"..\" segments.";
        }

        return null;
    }

    /**
     * Check that the target module exists: the module argument, or without it the
     * module set with module:use. Returns the error message, or null when it exists.
     *
     * The repository still throws ModuleNotFoundException for programmatic callers;
     * this check turns a wrong or missing module into one error line instead.
     */
    protected function validateModuleArgument(): ?string
    {
        if (! $this->hasArgument('module')) {
            return null;
        }

        $name = $this->argument('module');

        if (! is_string($name) || $name === '') {
            try {
                $this->moduleRepository()->getUsedNow();
            } catch (ModuleNotFoundException) {
                return 'No module given, and no module is set with module:use. Pass the module name, or run php artisan module:use <module> first.';
            }

            return null;
        }

        if ($this->moduleRepository()->find($name) === null) {
            return "Module [{$name}] does not exist.";
        }

        return null;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        return class_basename($this->argument($this->argumentName));
    }

    /**
     * Get default namespace.
     */
    public function getDefaultNamespace(): string
    {
        return '';
    }

    /**
     * Get class namespace.
     *
     * @param  Module  $module
     * @return string
     */
    public function getClassNamespace($module)
    {
        $path_namespace = $this->path_namespace(str_replace($this->getClass(), '', $this->argument($this->argumentName)));

        return $this->module_namespace($module->getStudlyName(), $this->getDefaultNamespace().($path_namespace ? '\\'.$path_namespace : ''));
    }

    public function module(?string $name = null): Module
    {
        return $this->moduleRepository()->findOrFail($name ?? $this->getModuleName());
    }
}
