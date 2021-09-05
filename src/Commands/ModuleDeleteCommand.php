<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class ModuleDeleteCommand extends Command
{
    protected $name = 'module:delete';
    protected $description = 'Delete a module from the application';

    public function handle() : int
    {
        $confirmation = $this->ask("Are you sure you want to delete module {$this->argument('module')}? (Y/n)");
        if( Str::startsWith(Str::lower($confirmation), 'y') ) {
            $this->laravel['modules']->delete($this->argument('module'));

            $this->info("Module {$this->argument('module')} has been deleted.");
        }
        else {
            $this->info("Module delete has been canceled");
        }
    }

    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module to delete.'],
        ];
    }
}
