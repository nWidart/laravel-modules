<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
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
        $module = $this->laravel['modules']->findOrFail($this->argument('module'));

        if ($module->enabled()) {
            $module->disable();

            $this->info("Module [{$module}] disabled successful.");
        } else {
            $this->comment("Module [{$module}] has already disabled.");
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
            ['module', InputArgument::REQUIRED, 'Module name.'],
        ];
    }
}
