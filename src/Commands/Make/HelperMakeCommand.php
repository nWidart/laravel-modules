<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class HelperMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-helper';

    protected $description = 'Create a new helper class for the specified module.';

    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('helpers')->getPath() ?? config('modules.paths.app_folder').'Helpers';

        return $path.$filePath.'/'.$this->getHelperName().'.php';
    }

    #[\Override]
    protected function getTemplateContents(): string
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'CLASS_NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    #[\Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the helper class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate an invokable class', null],
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the file if it already exists'],
        ];
    }

    /**
     * @return string|array<int, string>
     */
    protected function getHelperName(): array|string
    {
        return Str::studly($this->argument('name'));
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getHelperName());
    }

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.helpers.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.helpers.path', 'Helpers'));
    }

    protected function getStubName(): string
    {
        return $this->option('invokable') === true ? '/helper-invoke.stub' : '/helper.stub';
    }
}
