<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ObserverMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     */
    protected $name = 'module:make-observer';

    /**
     * The name of argument name.
     */
    protected $argumentName = 'name';

    /**
     * The console command description.
     */
    protected $description = 'Create a new observer for the specified module.';

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The observer name will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be created.'],
        ];
    }

    public function handle(): int
    {
        $this->components->info('Creating observer...');

        parent::handle();

        return 0;
    }

    protected function getTemplateContents(): mixed
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        $data = [
            'NAME' => $this->getObserverName(),
            'MODULE' => $this->getModuleName(),
            'MODULE_NAME_LOWER' => Str::lower($this->getModuleName()),
            'NAMESPACE' => $this->getClassNamespace($module),
            'MODEL' => $this->getModelNamespace().'\\'.$this->getModuleName(),
        ];

        return (new Stub('/observer.stub', $data))->render();
    }

    protected function getDestinationFilePath(): mixed
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $observerPath = GenerateConfigReader::read('observer');

        return $path.$observerPath->getPath().'/'.$this->getObserverName().'.php';
    }

    /**
     * Get the observer name.
     */
    private function getObserverName(): string
    {
        $name = $this->argument('name');
        $suffix = 'Observer';

        if (strpos($name, $suffix) === false) {
            $name .= $suffix;
        }

        return Str::studly($name);
    }

    /**
     * Get model namespace.
     */
    public function getModelNamespace(): string
    {
        $path = $this->laravel['modules']->config('paths.generator.model.path', 'app/Models');

        $path = str_replace('/', '\\', $path);

        return $this->laravel['modules']->config('namespace').'\\'.$this->laravel['modules']->findOrFail($this->getModuleName()).'\\'.$path;
    }

    private function getModelName(): mixed
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * Get default namespace.
     */
    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.observer.namespace') ?: $module->config('paths.generator.observer.path', 'app/Observers');
    }
}
