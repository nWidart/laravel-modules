<?php

namespace Nwidart\Modules\Commands;

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

    public function getDefaultNamespace() : string
    {
        if ($this->option('unit')) {
            return $this->laravel['modules']->config('paths.generator.test-unit.path', 'Tests/Unit');
        }
        return $this->laravel['modules']->config('paths.generator.test.path', 'Tests/Feature');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
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
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['unit', false, InputOption::VALUE_NONE, 'Create a unit test.'],
        ];
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());
        $stub = '/feature-test.stub';

        if ($this->option('unit')) {
            $stub = '/unit-test.stub';
        }
        return (new Stub($stub, [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        if ($this->option('unit')) {
            $testPath = GenerateConfigReader::read('test-unit');
        } else {
            $testPath = GenerateConfigReader::read('test');
        }

        return $path . $testPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
