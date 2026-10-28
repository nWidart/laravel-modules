<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;

class ClassMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:make-class
        {--t|type=class : The type of class, e.g. class, service, repository, contract, etc.}
        {--s|suffix : Create the class without the type suffix}
        {--i|invokable : Generate a single method, invokable class}
        {--f|force : Create the class even if the class already exists}
        {name : The name of the class}
        {module : The targeted module}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new class';

    protected $argumentName = 'name';

    #[\Override]
    public function getTemplateContents(): string
    {
        return (new Stub($this->stub(), [
            'NAMESPACE' => $this->getClassNamespace($this->module()),
            'CLASS' => $this->typeClass(),
        ]))->render();
    }

    public function stub(): string
    {
        return $this->option('invokable') ? '/class-invoke.stub' : '/class.stub';
    }

    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        return $path.$this->typePath($this->classPath()).'/'.$this->getFileName().'.php';
    }

    /**
     * Get the folder of plain classes, relative to the module root (e.g. "app/Classes").
     */
    protected function classPath(): string
    {
        return GenerateConfigReader::read('class')->getPath() ?? config('modules.paths.app_folder').'Classes';
    }

    protected function getFileName(): string
    {
        $file = Str::studly($this->argument('name'));

        if ($this->option('suffix') === true) {
            $names = [Str::plural($this->type()), Str::singular($this->type())];
            $file = Str::of($file)->remove($names, false);
            $file .= Str::of($this->type())->studly();
        }

        return $file;
    }

    /**
     * Get the type of class e.g. class, service, repository, etc.
     */
    protected function type(): string
    {
        return Str::of($this->option('type'))->remove('=')->singular();
    }

    protected function typePath(string $path): string
    {
        return ($this->type() === 'class') ? $path : Str::of($path)->replaceLast('Classes', Str::of($this->type())->plural()->studly());
    }

    public function typeClass(): string
    {
        return Str::of($this->getFileName())->basename()->studly();
    }

    /**
     * The namespace follows the folder the class is written to: "Classes" for the default type,
     * the plural type for other types (e.g. "Services" for --type=service).
     * A namespace configured for the type takes precedence. The generator config keys are
     * plural for some types and singular for others (services, interfaces, but class, repository),
     * so the plural key is read first, then the singular one.
     */
    #[\Override]
    public function getDefaultNamespace(): string
    {
        $type = $this->type();

        return config('modules.paths.generator.'.Str::plural($type).'.namespace')
            ?? config("modules.paths.generator.{$type}.namespace")
            ?? $this->typePath($this->strip_app_folder($this->classPath()));
    }
}
