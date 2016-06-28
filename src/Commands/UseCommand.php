<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class UseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:use';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use the specified module.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $module = Str::studly($this->argument('module'));

        if (!$this->laravel['modules']->has($module)) {
            $this->error("Module [{$module}] does not exists.");

            return;
        }

        $this->laravel['modules']->setUsed($module);

        $this->info("Module [{$module}] used successfully.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::REQUIRED, 'The name of module will be used.'),
        );
    }
}
