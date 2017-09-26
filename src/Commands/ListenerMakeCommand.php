<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Module;
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


    public function handle()
    {
        if ( ! $this->option('event')) {
            $this->error('The --event option is necessary');

            return;
        }

        parent::handle();
    }


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
            ['event', null, InputOption::VALUE_REQUIRED, 'Event name this is listening to', null],
        ];
    }


    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/listener.stub', [
            'NAMESPACE' => $this->getNamespace($module),
            "EVENTNAME" => $this->getEventName($module),
            "CLASS"     => $this->getClass(),
        ]))->render();
    }


    private function getNamespace($module)
    {
        $namespace = str_replace('/', '\\', config('modules.paths.generator.listener'));

        return $this->getClassNamespace($module)."\\".$namespace;
    }


    protected function getEventName(Module $module)
    {
        return $this->getClassNamespace($module)."\\".config('modules.paths.generator.event')."\\".$this->option('event');
    }


    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $seederPath = $this->laravel['modules']->config('paths.generator.listener');

        return $path.$seederPath.'/'.$this->getFileName().'.php';
    }


    /**
     * @return string
     */
    protected function getFileName()
    {
        return studly_case($this->argument('name'));
    }
}
