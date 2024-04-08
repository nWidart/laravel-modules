<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ComponentViewMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected string $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-component-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new component-view for the specified module.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the component.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return string
     */
    protected function getTemplateContents(): string
    {
        return (new Stub('/component-view.stub', ['QUOTE' => Inspiring::quote()]))->render();
    }

    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $factoryPath = GenerateConfigReader::read('component-view');

        return $path . $factoryPath->getPath() . '/' . $this->getFileName();
    }

    private function getFileName(): string
    {
        return Str::lower($this->argument('name')) . '.blade.php';
    }
}
