<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the factory.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/factory.stub', [
            'NAMESPACE'         => $this->getClassNamespace($module),
            'CLASS'             => $this->getClass(),
            'MODEL_CLASS'        => $this->getModelClass(),
            'MODEL_NAME'        => $this->getModelName(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $factoryPath = GenerateConfigReader::read('factory');

        return $path . $factoryPath->getPath() . '/' . $this->getFileName();
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass()
    {
        $studlyName = Str::studly( $this->argument($this->argumentName));

        if (!str_contains($studlyName, 'Factory'))
            $studlyName .= 'Factory';

        return $studlyName;
    }


    /**
     * Get model class name.
     *
     * @return string
     */
    public function getModelClass()
    {
        $model = $this->option('model');

        if (!$model) {
            $module = $this->laravel['modules']->findOrFail($this->getModuleName());
            $modules = $this->laravel['modules'];

            $namespace = $this->laravel['modules']->config('namespace');

            $namespace .= '\\' . $module->getStudlyName();

            $namespace .= '\\' .  $modules->config('paths.generator.model.path', 'Entities');

            $namespace .= '\\' .  str_replace('Factory', '', $this->argument($this->argumentName));

            if (class_exists($namespace))
                $model = $namespace;

        }

        return $model ?: 'Illuminate\\Database\\Eloquent\\Model';
    }


    /**
     * Get model class name.
     *
     * @return string
     */
    public function getModelName()
    {
        return class_basename($this->getModelClass());
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return $this->getClass() . '.php';
    }


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model to factory.', null],
        ];
    }

    public function getDefaultNamespace() : string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.factory.namespace') ?: $module->config('paths.generator.factory.path', 'Database\Factories');
    }

    /**
     * Get class namespace.
     *
     * @param \Nwidart\Modules\Module $module
     *
     * @return string
     */
    public function getClassNamespace($module)
    {
        $namespace = $this->laravel['modules']->config('namespace');

        $namespace .= '\\' . $module->getStudlyName();

        $namespace .= '\\' . $this->getDefaultNamespace();

        $namespace = str_replace('/', '\\', $namespace);

        return trim($namespace, '\\');
    }
}
