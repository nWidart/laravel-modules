<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;

class ClassCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:make-class
        {--t|type=class : The type of class, e.g. class, service, repository, contract, etc.}
        {--p|plain : Create the class without suffix (type)}
        {--i|invokable : Generate a single method, invokable class}
        {--f|force : Create the class even if the class already exists}
        {name : The name of the class}
        {module : The targeted module}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new class';

    protected $argumentName = 'name';

    public function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/app/Classes/Class.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    public function getDestinationFilePath()
    {
        $app_path = GenerateConfigReader::read('class')->getPath() ?? $this->app_path('Classes');
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $path .= $this->type_path($app_path) . '/' . $this->getFileName();

        return $path;
    }

    protected function getFileName()
    {
        $file = Str::studly($this->argument('name'));

        if ($this->option('plain') === false and $this->option('type') !== 'class') {
            $file .= $this->type();
        }

        return $file . '.php';
    }

    /**
     * Get the type of class e.g. Class, Services, Repository, etc.
     */
    protected function type()
    {
        return Str::studly($this->option('type'));
    }

    protected function type_path(string $path)
    {
        return ($this->option('type') === 'class') ? $path : Str::of($path)->replaceLast('Classes', Str::pluralStudly($this->type()));
    }

    /**
     * Get class name.
     */
    public function getClass(): string
    {
        return Str::of($this->getFileName())->remove('.php')->studly();
    }

    public function getDefaultNamespace(): string
    {
        $type = $this->option('type');

        return config("modules.paths.generator.{$type}.namespace") ?? $this->path_namespace(config("modules.paths.generator.{$type}.path") ?? $this->type_path($this->app_path('Classes')));
    }
}
