<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
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
    public function handle(): int
    {
        $this->components->info('Updating module ...');

        if ($name = $this->argument('module')) {
            $this->updateModule($name);

            return 0;
        }

        $this->updateAllModule();

        return 0;
    }


    protected function updateAllModule()
    {
        /** @var \Nwidart\Modules\Module $module */
        $modules = $this->laravel['modules']->getOrdered();

        foreach ($modules as $module) {
            $this->updateModule($module);
        }

    }

    protected function updateModule($name)
    {

        if ($name instanceof Module) {
            $module = $name;
        }else {
            $module = $this->laravel['modules']->findOrFail($name);
        }

        $this->components->task("Updating {$module->getName()} module", function () use ($module) {
            $this->laravel['modules']->update($module);
        });
        $this->laravel['modules']->update($name);

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
