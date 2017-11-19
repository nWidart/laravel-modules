<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Traits\MigrationLoaderTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateRollbackCommand extends Command
{
    use MigrationLoaderTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the modules migrations.';

    /**
     * @var \Nwidart\Modules\Repository
     */
    protected $module;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->module = $this->laravel['modules'];

        $name = $this->argument('module');

        if (!empty($name)) {
            $this->rollback($name);

            return;
        }

        foreach ($this->module->getOrdered($this->option('direction')) as $module) {
            $this->line('Running for module: <info>' . $module->getName() . '</info>');

            $this->rollback($module);
        }
    }

    /**
     * Rollback migration from the specified module.
     *
     * @param $module
     */
    public function rollback($module)
    {
        if (is_string($module)) {
            $module = $this->module->findOrFail($module);
        }

        $migrator = new Migrator($module);

        $database = $this->option('database');

        if (!empty($database)) {
            $migrator->setDatabase($database);
        }

        $migrated = $migrator->rollback();

        if (count($migrated)) {
            foreach ($migrated as $migration) {
                $this->line("Rollback: <info>{$migration}</info>");
            }

            return;
        }

        $this->comment('Nothing to rollback.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'desc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
        ];
    }
}
