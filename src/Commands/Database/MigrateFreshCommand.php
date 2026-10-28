<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Console\View\TaskResult;
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

    /**
     * The migration paths registered on the migrator before the module migrations run.
     *
     * @var Collection<int, string>
     */
    protected Collection $migration_paths;

    public function __construct()
    {
        parent::__construct();

        $this->migrator = app('migrator');
        $this->migration_paths = collect($this->migrator->paths());
    }

    #[\Override]
    public function handle(): int
    {
        // db:wipe cannot pretend, and pretending the migrations on the current database would
        // only show the pending ones, not what runs after the wipe: refuse instead of dropping tables
        if ($this->option('pretend')) {
            $this->components->error('--pretend is not supported by module:migrate-fresh: it would still drop all tables.');

            return self::FAILURE;
        }

        if (! $this->hasModulesToRun()) {
            return self::FAILURE;
        }

        // an unknown module must fail before the database is wiped
        $this->validateModules();

        // confirm before anything destructive runs, the nested calls below are forced
        if (! $this->canProceed()) {
            return self::FAILURE;
        }

        // drop tables
        $wiped = false;

        $this->components->task('Dropping all tables', function () use (&$wiped) {
            $wiped = $this->callSilent('db:wipe', array_filter([
                '--database' => $this->option('database'),
                '--drop-views' => $this->option('drop-views'),
                '--drop-types' => $this->option('drop-types'),
                '--force' => true,
            ])) === self::SUCCESS;

            return $wiped ? TaskResult::Success->value : TaskResult::Failure->value;
        });

        if (! $wiped) {
            return self::FAILURE;
        }

        // create migration table
        if ($this->call('migrate:install', array_filter([
            '--database' => $this->option('database'),
        ])) !== self::SUCCESS) {
            return self::FAILURE;
        }

        // run migration of root
        $root_paths = $this->migration_paths
            ->push($this->laravel->databasePath().DIRECTORY_SEPARATOR.'migrations')
            ->reject(fn (string $path) => str_starts_with($path, config('modules.paths.modules')));

        if ($root_paths->count() > 0) {
            $this->components->twoColumnDetail('Running Migration of <fg=cyan;options=bold>Root</>');

            if ($this->call('migrate', array_filter([
                '--path' => $root_paths->toArray(),
                '--database' => $this->option('database'),
                '--force' => true,
                '--realpath' => true,
            ])) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        $this->executeModules();

        return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        if ($this->call('module:migrate', array_filter([
            'module' => $module->getStudlyName(),
            '--database' => $this->option('database'),
            '--force' => true,
            '--seed' => $this->option('seed'),
        ])) !== self::SUCCESS) {
            $this->markAsFailed();
        }
    }

    /**
     * Get the console command options.
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Not supported: the command refuses to run, as it would still drop all tables.'],
            ['drop-views', null, InputOption::VALUE_NONE, 'Drop all tables and views'],
            ['drop-types', null, InputOption::VALUE_NONE, 'Drop all tables and types (Postgres only)'],
        ];
    }
}
