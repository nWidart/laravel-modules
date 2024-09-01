<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Support\Str;
use Nwidart\Modules\Commands\Make\GeneratorCommand;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class MakeFactoryCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     */
    protected $argumentName = 'name';

    /**
     * The console command name.
     */
    protected $name = 'module:make-factory';

    /**
     * The console command description.
     */
    protected $description = 'Create a new model factory for the specified module.';

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getTemplateContents(): mixed
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/database/factories/factory.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'NAME' => $this->getModelName(),
            'MODEL_NAMESPACE' => $this->getModelNamespace(),
        ]))->render();
    }

    protected function getDestinationFilePath(): mixed
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $factoryPath = GenerateConfigReader::read('factory');

        return $path.$factoryPath->getPath().'/'.$this->getFileName();
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name')).'Factory.php';
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
        return config('modules.paths.generator.factory.namespace')
            ?? ltrim(config('modules.paths.generator.factory.path', 'Database/Factories'), config('modules.paths.app_folder', ''));
    }

    /**
     * Get model namespace.
     */
    public function getModelNamespace(): string
    {
        $path = ltrim(config('modules.paths.generator.model.path', 'Entities'), config('modules.paths.app_folder', ''));

        $path = str_replace('/', '\\', $path);

        return $this->laravel['modules']->config('namespace').'\\'.$this->laravel['modules']->findOrFail($this->getModuleName()).'\\'.$path;
    }
}
