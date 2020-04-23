<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;

class DisableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable the specified module.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * check if user entred an argument
         */
        if ($this->argument('module') === null) {
            $this->disableAll();
        }

        /** @var Module $module */
        $module = $this->laravel['modules']->findOrFail($this->argument('module'));

        if ($module->isEnabled()) {
            $module->disable();

            $this->info("Module [{$module}] disabled successful.");
        } else {
            $this->comment("Module [{$module}] has already disabled.");
        }
    }

    /**
     * disableAll
     *
     * @return void
     */
    public function disableAll()
    {
        /** @var Module[] $modules */
        $modules = $this->laravel['modules']->all();

        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $module->disable();

                $this->info("Module [{$module}] disabled successful.");
            } else {
                $this->comment("Module [{$module}] has already disabled.");
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'Module name.'],
        ];
    }
}
