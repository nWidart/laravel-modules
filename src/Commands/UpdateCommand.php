<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

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
    public function handle()
    {
        $name = $this->argument('module');

        if ($name) {
            $this->updateModule($name);

            return;
        }

        /** @var \Nwidart\Modules\Module $module */
        foreach ($this->laravel['modules']->getOrdered() as $module) {
            $this->updateModule($module->getName());
        }
    }

    protected function updateModule($name)
    {
        $this->line('Running for module: <info>' . $name . '</info>');

        $this->laravel['modules']->update($name);

        $this->info("Module [{$name}] updated successfully.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be updated.'],
        ];
    }
}
