<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class EnumMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-enum';

    protected $description = 'Create a new enum class for the specified module.';

    #[\Override]
    public function getDestinationFilePath(): string
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('enums')->getPath() ?? config('modules.paths.app_folder').'Enums';

        return $path.$filePath.'/'.$this->getEnumName().'.php';
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
            ['name', InputArgument::REQUIRED, 'The name of the enum class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite the file if it already exists'],
        ];
    }

    /**
     * @return string|array<int, string>
     */
    protected function getEnumName(): array|string
    {
        return Str::studly($this->argument('name'));
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getEnumName());
    }

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.enums.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.enums.path', 'Enums'));
    }

    protected function getStubName(): string
    {
        return '/enum.stub';
    }
}
