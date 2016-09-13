<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate the migrations from the specified module or from all modules.';

    /**
     * @var \Nwidart\Modules\Repository
     */
    protected $module;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->module = $this->laravel['modules'];

        $name = $this->argument('module');

        if ($name) {
            return $this->migrate($name);
        }

        foreach ($this->module->getOrdered($this->option('direction')) as $module) {
            $this->line('Running for module: <info>' . $module->getName() . '</info>');

            $this->migrate($module);
        }
    }

    /**
     * Run the migration from the specified module.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function migrate($name)
    {
        $module = $this->module->findOrFail($name);

        $this->call('migrate', [
            '--path' => $this->getPath($module),
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
        ]);

        if ($this->option('seed')) {
            $this->call('module:seed', ['module' => $name]);
        }
    }

    /**
     * Get migration path for specific module.
     *
     * @param  \Nwidart\Modules\Module $module
     * @return string
     */
    protected function getPath($module)
    {
        $config = $module->get('migration');

        $path = (is_array($config) && array_key_exists('path', $config)) ? $config['path'] : config('modules.paths.generator.migration');

        $path = $module->getExtraPath($path);

        return str_replace(base_path(), '', $path);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module will be used.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
        );
    }
}
