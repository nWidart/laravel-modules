<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputArgument;

class DumpCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump-autoload the specified module or for all module.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Generating optimized autoload modules.');

        if ($name = $this->argument('module') ) {
            $this->dump($name);

            return 0;
        }

        $this->dumpAll();

        return 0;
    }

    /**
     * dumpAll
     *
     * @return void
     */
    public function dumpAll()
    {
        /** @var Modules $modules */
        $modules = $this->laravel['modules']->all();

        foreach ($modules as $module) {
            $this->dump($module);
        }
    }

    public function dump($name)
    {
        if ($name instanceof Module) {
            $module = $name;
        } else {
            $module = $this->laravel['modules']->findOrFail($name);
        }

        $this->components->task("$module", function () use ($module) {
            chdir($module->getPath());

            passthru('composer dump -o -n -q');
        });

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
