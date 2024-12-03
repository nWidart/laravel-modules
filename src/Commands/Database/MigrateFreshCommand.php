<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Symfony\Component\Console\Input\InputOption;

class MigrateFreshCommand extends BaseCommand implements ConfirmableCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all database tables and re-run the modules migrations.';

    /**
     * The migrator instance.
     */
    protected Migrator $migrator;

    protected Collection $migration_paths;

    public function __construct()
    {
        parent::__construct();

        $this->migrator = app('migrator');
        $this->migration_paths = collect($this->migrator->paths());
    }

    public function handle(): void
    {
        // drop tables
        $this->components->task('Dropping all tables', fn () => $this->callSilent('db:wipe', array_filter([
            '--database' => $this->option('database'),
            '--drop-views' => $this->option('drop-views'),
            '--drop-types' => $this->option('drop-types'),
            '--force' => true,
        ])) == 0);

        // create migration table
        $this->call('migrate:install', array_filter([
            '--database' => $this->option('database'),
        ])) == 0;

        // run migration of root
        $root_paths = $this->migration_paths
            ->push($this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations')
            ->reject(fn (string $path) => str_starts_with($path, config('modules.paths.modules')));

        if ($root_paths->count() > 0) {
            $this->components->twoColumnDetail('Running Migration of <fg=cyan;options=bold>Root</>');

            $this->call('migrate', array_filter([
                '--path' => $root_paths->toArray(),
                '--database' => $this->option('database'),
                '--pretend' => $this->option('pretend'),
                '--force' => $this->option('force'),
                '--realpath' => true,
            ]));
        }

        parent::handle();
    }

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->call('module:migrate', array_filter([
            'module' => $module->getStudlyName(),
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
            '--seed' => $this->option('seed'),
        ]));
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
        ];
    }
}
