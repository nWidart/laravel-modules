<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ObserverMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-observer';

    protected $argumentName = 'name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new observer class for the specified module.';

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    public function getDefaultNamespace() : string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.observers.namespace') ?: $module->config('paths.generator.observers.path', 'Observers');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    public function getModelNamespace() : string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.model.namespace') ?: $module->config('paths.generator.model.path', 'Entities');
    }

    /**
     * Get template contents.
     *
     * @return string
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        if($this->option('model') ) {
            return (new Stub('/observer.stub', [
                'NAMESPACE' => $this->getClassNamespace($module),
                'CLASS'     => $this->getClass(),

                'NAMESPACEDMODEL'     => $this->getNamespacedModel(),
                'DUMMYMODEL'     => $this->getModel(),
                'MODEL'     => $this->getModelVariable(),
            ]))->render();
        }

        return (new Stub('/observer.plain.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     *
     * @return string
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $notificationPath = GenerateConfigReader::read('observers');

        return $path . $notificationPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * @return string
     */
    private function getNamespacedModel()
    {
        $model = $this->option('model');

        $model = str_replace('/', '\\', $model);

        $module = $this->laravel['modules']->findOrFail($this->getModuleName());
        $namespaceModel = $this->getClassNamespace($module, $this->getModelNamespace() ) . '\\' . $model;

        if (Str::startsWith($model, '\\')) {
            return trim($model, '\\');
        }

        return $namespaceModel;
    }

    /**
     * @return string
     */
    private function getModel()
    {
        $model = $this->option('model');

        $model = class_basename(trim($model, '\\'));

        return $model;
    }

    /**
     * @return string
     */
    private function getModelVariable()
    {
        $model = $this->getModel();

        return '$' . Str::camel($model);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the observer class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the observer applies to.'],
        ];
    }
}
