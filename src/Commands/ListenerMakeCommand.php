<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ListenerMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event listener class for the specified module';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command.'],
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
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for.'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued.'],
        ];
    }

    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getNamespace($module),
            'EVENTNAME' => $this->getEventName($module),
            'SHORTEVENTNAME' => $this->option('event'),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    private function getNamespace($module)
    {
        $listenerPath = GenerateConfigReader::read('listener');

        $namespace = str_replace('/', '\\', $listenerPath->getPath());

        return $this->getClassNamespace($module) . "\\" . $namespace;
    }

    protected function getEventName(Module $module)
    {
        $eventPath = GenerateConfigReader::read('event');

        return $this->getClassNamespace($module) . "\\" . $eventPath->getPath() . "\\" . $this->option('event');
    }

    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $listenerPath = GenerateConfigReader::read('listener');

        return $path . $listenerPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return studly_case($this->argument('name'));
    }

    /**
     * @return string
     */
    protected function getStubName(): string
    {
        if ($this->option('queued')) {
            if ($this->option('event')) {
                return '/listener-queued.stub';
            }

            return '/listener-queued-duck.stub';
        }

        if ($this->option('event')) {
            return '/listener.stub';
        }

        return '/listener-duck.stub';
    }
}
