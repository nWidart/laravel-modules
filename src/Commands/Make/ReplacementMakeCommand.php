<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReplacementMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of the console command.
     */
    protected $name = 'module:make-replacement';

    /**
     * The console command description.
     */
    protected $description = 'Create a new replacement key command class for stubs.';

    protected $argumentName = 'name';

    public function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('command_replacements')->getPath() ?? config('modules.paths.app_folder').'Console/Replacements';

        return $path.$filePath.'/'.$this->getFileName().'.php';
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the enum class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'su.'],
        ];
    }

    protected function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }

    private function getClassNameWithoutNamespace(): array|string
    {
        return class_basename($this->getFileName());
    }

    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.command_replacements.namespace')
            ?? ltrim(config('modules.paths.generator.command_replacements.path', 'Console/Replacements'), config('modules.paths.app_folder', ''));
    }

    protected function getStubName(): string
    {
        return '/replacement.stub';
    }
}
