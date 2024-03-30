<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RuleMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     */
    protected $argumentName = 'name';

    /**
     * The console command name.
     */
    protected $name = 'module:make-rule';

    /**
     * The console command description.
     */
    protected $description = 'Create a new validation rule for the specified module.';

    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.rules.namespace', $this->getPathNamespace(config('modules.paths.generator.rules.path', 'app/Rules')));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the rule class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['implicit', 'i', InputOption::VALUE_NONE, 'Generate an implicit rule'],
        ];
    }

    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        $stub = $this->option('implicit')
            ? '/rule.implicit.stub'
            : '/rule.stub';

        return (new Stub($stub, [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getFileName(),
        ]))->render();
    }

    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $rulePath = GenerateConfigReader::read('rules');

        return $path . $rulePath->getPath() . '/' . $this->getFileName() . '.php';
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
