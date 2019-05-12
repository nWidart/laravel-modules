<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Traits\MigrationLoaderTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateResetCommand extends Command
{
    use MigrationLoaderTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the modules migrations.';

    /**
     * @var \Nwidart\Modules\Contracts\RepositoryInterface
     */
    protected $module;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->module = $this->laravel['modules'];

        $name = $this->argument('module');

        if (!empty($name)) {
            $this->resetOne($name);

            return;
        }

        $this->resetMany($this->module->getOrdered($this->option('direction')));
    }

    /**
     * Rollback migration from the specified module.
     *
     * @param $module
     */
    public function resetOne($module): void
    {
        if (is_string($module)) {
            $module = $this->module->findOrFail($module);
        }

        $path = str_replace(base_path(), '', (new Migrator($module))->getPath());

        $this->call('migrate:reset', [
            '--path' => $path,
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Rollback migration from the all the specified modules.
     *
     * @param array $modules
     */
    private function resetMany(array $modules): void
    {
        $paths = [];
        foreach ($modules as $module) {
            $path = str_replace(base_path(), '', (new Migrator($module))->getPath());
            $paths[] = $path;
        }

        $this->call('migrate:reset', [
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
        ]);
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
