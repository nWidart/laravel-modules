<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ModuleDeleteCommand extends Command
{
    /**
     * @var string
     */
    protected $name = 'module:delete';
    /**
     * @var string
     */
    protected $description = 'Delete a module from the application';

    public function handle()
    {
        $this->laravel['modules']->delete($this->argument('module'));

        $this->info("Module {$this->argument('module')} has been deleted.");
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module to delete.'],
        ];
    }
}
