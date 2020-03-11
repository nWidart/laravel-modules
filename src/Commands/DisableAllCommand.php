<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;

class DisableAllCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:disable-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable all modules.';

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
            if ($module->isEnabled()) {
                $module->disable();
    
                $this->info("Module [{$module}] disabled successful.");
            } else {
                $this->comment("Module [{$module}] has already disabled.");
            }
        }
    }
}
