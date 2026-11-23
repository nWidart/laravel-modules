<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Database\Migrations\Migrator as LaravelMigrator;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Traits\WritesJsonOutput;
use Symfony\Component\Console\Input\InputOption;

class MigrateStatusCommand extends BaseCommand
{
    use WritesJsonOutput;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Status for all module migrations';

    /**
     * @var RepositoryInterface
     */
    protected $module;

    /**
     * The status of each module, in run order, for the JSON document.
     *
     * @var array<int, array{name: string, migrations: array<int, array{name: string, ran: bool, batch: int|null}>}>
     */
    private array $statuses = [];

    /**
     * The batch of each migration that ran, keyed by migration name; null when the
     * migration table does not exist. Read once per run, in JSON mode only.
     *
     * @var array<string, int>|null
     */
    private ?array $batches = null;

    private bool $batchesLoaded = false;

    /**
     * Execute the console command.
     *
     * @return int
     */
    #[\Override]
    public function handle()
    {
        $this->enterJsonMode();

        $this->statuses = [];
        $this->batches = null;
        $this->batchesLoaded = false;

        $code = parent::handle();

        if ($this->inJsonMode()) {
            $this->writeJson([
                'modules' => $this->statuses,
                'migration_table' => $this->migrationBatches() !== null,
            ]);
        }

        return $code;
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        // the same paths module:migrate, module:migrate-rollback and module:migrate-reset use
        $paths = (new Migrator($module, $this->getLaravel()))->getPaths();

        if ($this->inJsonMode()) {
            $this->statuses[] = [
                'name' => $module->getName(),
                'migrations' => $this->migrationStatuses($paths),
            ];

            return;
        }

        if ($this->call('migrate:status', [
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--realpath' => true,
        ]) !== self::SUCCESS) {
            $this->markAsFailed();
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return null;
    }

    /**
     * Get the status of the migrations in the given paths, sorted by name as migrate:status
     * sorts them. A migration ran when the migration table lists it.
     *
     * @param  array<int, string>  $paths
     * @return array<int, array{name: string, ran: bool, batch: int|null}>
     */
    private function migrationStatuses(array $paths): array
    {
        $batches = $this->migrationBatches() ?? [];

        $statuses = [];

        foreach (array_keys($this->laravelMigrator()->getMigrationFiles($paths)) as $migration) {
            $migration = (string) $migration;

            $statuses[] = [
                'name' => $migration,
                'ran' => array_key_exists($migration, $batches),
                'batch' => $batches[$migration] ?? null,
            ];
        }

        return $statuses;
    }

    /**
     * Get the batch of each migration that ran on the --database connection, keyed by
     * migration name, or null when the migration table does not exist.
     *
     * @return array<string, int>|null
     */
    private function migrationBatches(): ?array
    {
        if ($this->batchesLoaded) {
            return $this->batches;
        }

        $migrator = $this->laravelMigrator();

        $this->batches = $migrator->usingConnection($this->option('database'), function () use ($migrator) {
            if (! $migrator->repositoryExists()) {
                return null;
            }

            return array_map(intval(...), $migrator->getRepository()->getMigrationBatches());
        });
        $this->batchesLoaded = true;

        return $this->batches;
    }

    /**
     * Get Laravel's migrator, the one migrate:status uses.
     */
    private function laravelMigrator(): LaravelMigrator
    {
        return $this->laravel->make('migrator');
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['json', null, InputOption::VALUE_NONE, 'Print the status as one JSON document.'],
        ];
    }
}
