<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Module;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Pingpong\Support\Stub;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateListenerCommand extends GeneratorCommand
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
    protected $description = 'Generate a new Listener Class for the specified module';

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
            ['event', null, InputOption::VALUE_REQUIRED, 'An example option.', null],
        ];
    }

    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/listener.stub', [
            'NAMESPACE' => $this->getClassNamespace($module) . "\\" . config("modules.paths.generator.listener"),
            "EVENTNAME" => $this->getEventName($module),
            "EVENTSHORTENEDNAME" => $this->option('event'),
            "CLASS" => $this->getClass(),
            'DUMMYNAMESPACE' => $this->laravel->getNamespace() . "Events",
        ]))->render();
    }

    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $seederPath = $this->laravel['modules']->config('paths.generator.listener');

        return $path . $seederPath . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        return studly_case($this->argument('name'));
    }

    public function fire()
    {
        if (!$this->option('event')) {
            return $this->error("The --event option is neccessary");
        }

        parent::fire();
    }

    protected function getEventName(Module $module)
    {
        return $this->getClassNamespace($module) . "\\" . config("modules.paths.generator.event") . "\\" . $this->option('event');
    }
}
