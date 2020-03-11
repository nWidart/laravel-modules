<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;

class EnableAllCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:enable-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable all modules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /** @var Modules $modules */
        $modules = $this->laravel['modules']->all();

        // enable all modules
        foreach( $modules as $module )
        {
            if ($module->isDisabled()) {
                $module->enable();
    
                $this->info("Module [{$module}] enabled successful.");
            } else {
                $this->comment("Module [{$module}] has already enabled.");
            }
        }
    }
}
