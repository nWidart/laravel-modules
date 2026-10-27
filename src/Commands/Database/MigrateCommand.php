<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Filesystem\Filesystem;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Migrations\Migrator;
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
     * @param  string  $name
     */
    #[\Override]
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
        $migrator = new Migrator($module, $this->getLaravel(), $this->option('subpath'));

        if ($subpath = $this->option('subpath')) {
            if (! $migrator->subpathExists()) {
                $this->components->error("Migration subpath [{$subpath}] does not exist in module [{$module->getName()}].");

                return null;
            }

            // a directory, a single migration file, or a glob pattern matching migration files
            $path = $migrator->getMigrationsPath();

            return is_dir($path) ? [$path] : $this->laravel->make(Filesystem::class)->glob($path);
        }

        // Always includes the module's own migration path, so a disabled module (whose
        // paths are not auto-registered) never falls back to migrating the whole app.
        return $migrator->getPaths();
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
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
            ['subpath', null, InputOption::VALUE_OPTIONAL, 'A migrations subdirectory, file, or glob pattern, relative to the module migrations directory'],
        ];
    }
}
