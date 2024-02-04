<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ModuleDeleteCommand extends Command
{
    protected $name = 'module:delete';
    protected $description = 'Delete module(s) from the application.';

    public function handle(): int
    {
        $modules = $this->argument('module');

        foreach ($modules as $module) {
            $this->laravel['modules']->delete($module);
            $this->components->info("Module {$module} has been deleted.");
        }

        return 0;
    }

    protected function getArguments()
    {
        return [
            ['module', InputArgument::IS_ARRAY, 'The name of module to delete.'],
        ];
    }
}
