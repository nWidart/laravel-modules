<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Events\ModuleCreated;
use Nwidart\Modules\Events\ModuleDeleted;
use Symfony\Component\Console\Input\InputArgument;

class ModuleDeleteCommand extends Command
{
    protected $name = 'module:delete';
    protected $description = 'Delete a module from the application';

    public function handle() : int
    {
        $module = $this->argument('module');

        $this->laravel['modules']->delete($module);

        $this->info("Module {$module} has been deleted.");

        event(new ModuleDeleted($module));

        return 0;
    }

    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module to delete.'],
        ];
    }
}
