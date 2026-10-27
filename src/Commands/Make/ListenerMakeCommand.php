<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
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
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
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
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for.'],
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued.'],
        ];
    }

    #[\Override]
    protected function getTemplateContents()
    {
        $module = $this->moduleRepository()->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getClassNamespace($module),
            'EVENTNAME' => $this->getEventName($module),
            'SHORTEVENTNAME' => $this->getShortEventName(),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    #[\Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.listener.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.listener.path', 'Listeners'));
    }

    /**
     * @return string
     */
    protected function getEventName(Module $module)
    {
        $event = str_replace('/', '\\', (string) $this->option('event'));

        if ($this->isQualifiedEventName($event)) {
            return ltrim($event, '\\');
        }

        $namespace = $this->module_namespace($module->getStudlyName(), $this->getEventNamespace());

        return $namespace.'\\'.$event;
    }

    /**
     * Whether the event is already a fully qualified class name and must not get the module events namespace.
     * Same rule as Laravel's make:listener, plus events of any module: a leading "\",
     * or a name in the app, Illuminate, or modules namespace.
     */
    protected function isQualifiedEventName(string $event): bool
    {
        $prefixes = ['\\', $this->laravel->getNamespace(), 'Illuminate\\'];

        $modulesNamespace = trim((string) config('modules.namespace', 'Modules'), '\\');

        if ($modulesNamespace !== '') {
            $prefixes[] = $modulesNamespace.'\\';
        }

        return Str::startsWith($event, $prefixes);
    }

    /**
     * Get the namespace of the module's events, relative to the module namespace.
     * Same rule as module:make-event: the configured namespace, else the event path without the app folder.
     */
    protected function getEventNamespace(): string
    {
        return config('modules.paths.generator.event.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.event.path', 'Events'));
    }

    /**
     * @return string
     */
    protected function getShortEventName()
    {
        return class_basename($this->option('event'));
    }

    #[\Override]
    protected function getDestinationFilePath()
    {
        $path = $this->moduleRepository()->getModulePath($this->getModuleName());

        $listenerPath = GenerateConfigReader::read('listener');

        return $path.$listenerPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return Str::studly($this->argument('name'));
    }

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
