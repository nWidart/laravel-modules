<?php

namespace Nwidart\Modules\Commands\Make;

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
    protected $argumentName = 'name';

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
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getTemplateContents()
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub('/factory.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'NAME' => $this->getModelName(),
            'MODEL_NAMESPACE' => $this->getModelNamespace(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getDestinationFilePath()
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $factoryPath = GenerateConfigReader::read('factory');

        return $path.$factoryPath->getPath().'/'.$this->getFileName();
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return $this->getClassPath().'Factory.php';
    }

    /**
     * The model's class name, without its folders.
     */
    private function getModelName(): string
    {
        return $this->getClass();
    }

    /**
     * Get default namespace.
     */
    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.factory.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.factory.path', 'Database/Factories'));
    }

    /**
     * Get model namespace: the models' namespace, with the folders of the name argument.
     */
    public function getModelNamespace(): string
    {
        $path = $this->strip_app_folder(config('modules.paths.generator.model.path', 'Entities'));

        $folder = $this->getClassFolder();

        $path = str_replace('/', '\\', $path.($folder !== '' ? '/'.$folder : ''));

        return $this->moduleRepository()->config('namespace').'\\'.$this->moduleRepository()->findOrFail($this->getModuleName()).'\\'.$path;
    }
}
