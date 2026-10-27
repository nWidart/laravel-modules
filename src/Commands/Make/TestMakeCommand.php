<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TestMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'module:make-test';

    protected $description = 'Create a new test class for the specified module.';

    #[\Override]
    public function getDefaultNamespace(): string
    {
        if ($this->option('feature')) {
            return config('modules.paths.generator.test-feature.namespace')
                ?? config('modules.paths.generator.test-feature.path', 'tests/Feature');
        }

        return config('modules.paths.generator.test-unit.namespace')
            ?? config('modules.paths.generator.test-unit.path', 'tests/Unit');
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the form request class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['feature', null, InputOption::VALUE_NONE, 'Create a feature test.'],
        ];
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getTemplateContents()
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());
        $stub = '/tests/'.(($this->option('feature')) ? 'feature' : 'unit').'.stub';

        return (new Stub($stub, [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    #[\Override]
    protected function getDestinationFilePath()
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        if ($this->option('feature')) {
            $testPath = GenerateConfigReader::read('test-feature');
        } else {
            $testPath = GenerateConfigReader::read('test-unit');
        }

        return $path.$testPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
