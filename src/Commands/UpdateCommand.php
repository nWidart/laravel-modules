<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateCommand extends Command
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dependencies for the specified module or for all modules.';

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $this->laravel['modules']->update($this->getModuleName(), $this->option('copy-to-main'));

        $this->info("Module [{$name}] updated successfully.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module will be updated.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('copy-to-main', null, InputOption::VALUE_NONE, 'Save to main composer.json.')
        );
    }
}
