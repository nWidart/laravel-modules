<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class FactoryMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected string $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory for the specified module.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/factory.stub', [
            'NAMESPACE'       => $this->getClassNamespace($module),
            'NAME'            => $this->getModelName(),
            'MODEL_NAMESPACE' => $this->getModelNamespace(),
        ]))->render();
    }

    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $factoryPath = GenerateConfigReader::read('factory');

        return $path . $factoryPath->getPath() . '/' . $this->getFileName();
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name')) . 'Factory.php';
    }

    private function getModelName(): string
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.factory.namespace')
            ?? ltrim(config('modules.paths.generator.factory.path', 'Database/Factories'), config('modules.paths.app_folder', ''));
    }

    /**
     * Get model namespace.
     *
     * @return string
     */
    public function getModelNamespace(): string
    {
        $path = ltrim(config('modules.paths.generator.model.path', 'Entities'), config('modules.paths.app_folder', ''));

        $path = str_replace('/', '\\', $path);

        return $this->laravel['modules']->config('namespace') . '\\' . $this->laravel['modules']->findOrFail($this->getModuleName()) . '\\' . $path;
    }
}
