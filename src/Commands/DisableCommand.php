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
    public function handle(): int
    {
        $this->components->info('Disabling module ...');

        if ($name = $this->argument('module') ) {
            $this->disable($name);

            return 0;
        }

        $this->disableAll();

        return 0;
    }

    /**
     * disableAll
     *
     * @return void
     */
    public function disableAll()
    {
        /** @var Modules $modules */
        $modules = $this->laravel['modules']->all();

        foreach ($modules as $module) {
            $this->disable($module);
        }
    }

    /**
     * disable
     *
     * @param string $name
     * @return void
     */
    public function disable($name)
    {
        if ($name instanceof Module) {
            $module = $name;
        }else {
            $module = $this->laravel['modules']->findOrFail($name);
        }

        if ($module->isEnabled()) {
            $module->disable();

            $this->components->info("Module [{$module}] disabled successful.");
        } else {
            $this->components->warn("Module [{$module}] has already disabled.");
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
