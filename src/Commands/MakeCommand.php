<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Generators\ModuleGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new module.';

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $names = $this->argument('name');

        foreach ($names as $name) {
            with(new ModuleGenerator($name))
                ->setFilesystem($this->laravel['files'])
                ->setModule($this->laravel['modules'])
                ->setConfig($this->laravel['config'])
                ->setConsole($this)
                ->setForce($this->option('force'))
                ->setPlain($this->option('plain'))
                ->generate();
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::IS_ARRAY, 'The names of modules will be created.'),
        );
    }

    protected function getOptions()
    {
        return [
            array('plain', 'p', InputOption::VALUE_NONE, 'Generate a plain module (without some resources).'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when module already exist.'),
        ];
    }
}
