<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Migrations\Migrator as ModuleMigrator;
use Nwidart\Modules\Module;
use Symfony\Component\Console\Input\InputOption;

class MigrateCommand extends BaseCommand
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
     * The migrator instance.
     */
    protected Migrator $migrator;

    protected Collection $migration_list;

    public function __construct()
    {
        parent::__construct();

        $this->migrator = app('migrator');
        $this->migration_list = collect($this->migrator->paths());
    }

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->twoColumnDetail("Running Migration <fg=cyan;options=bold>{$module->getName()}</> Module");

        $paths = $this->getMigrationPaths($module);

        if (is_null($paths)) {
            $this->markAsFailed();

            return;
        }

        $status = $this->call('migrate', array_filter([
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
            '--realpath' => true,
        ]));

        if ($status !== self::SUCCESS) {
            $this->markAsFailed();

            return;
        }

        if ($this->option('seed') && $this->call('module:seed', array_filter([
            'module' => $module->getName(),
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
        ])) !== self::SUCCESS) {
            $this->markAsFailed();
        }
    }

    /**
     * Get the migration paths of the module, or null when the given --subpath does not exist.
     *
     * @return array<int, string>|null
     */
    protected function getMigrationPaths(Module $module): ?array
    {
        $migrator = new ModuleMigrator($module, $this->getLaravel(), $this->option('subpath'));

        if ($subpath = $this->option('subpath')) {
            if (! $migrator->subpathExists()) {
                $this->components->error("Migration subpath [{$subpath}] does not exist in module [{$module->getName()}].");

                return null;
            }

            // a directory, a single migration file, or a glob pattern matching migration files
            $path = $migrator->getMigrationsPath();

            return is_dir($path) ? [$path] : $this->laravel['files']->glob($path);
        }

        $module_path = $module->getPath();

        // Always include the module's own migration path, so a disabled module (whose
        // paths are not auto-registered) never falls back to migrating the whole app.
        return $this->migration_list
            ->filter(fn ($path) => Str::startsWith($path, [$module_path.'/', $module_path.DIRECTORY_SEPARATOR]))
            ->push($migrator->getPath())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
            ['subpath', null, InputOption::VALUE_OPTIONAL, 'A migrations subdirectory, file, or glob pattern, relative to the module migrations directory'],
        ];
    }
}
