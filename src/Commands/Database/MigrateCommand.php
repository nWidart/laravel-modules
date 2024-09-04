<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Nwidart\Modules\Commands\BaseCommand;
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

        $module_path = $module->getPath();

        $paths = $this->migration_list
            ->filter(fn ($path) => str_starts_with($path, $module_path));

        $this->call('migrate', array_filter([
            '--path' => $paths->toArray(),
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
            '--realpath' => true,
        ]));

        if ($this->option('seed')) {
            $this->call('module:seed', ['module' => $module->getName(), '--force' => $this->option('force')]);
        }

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
            ['subpath', null, InputOption::VALUE_OPTIONAL, 'Indicate a subpath to run your migrations from'],
        ];
    }
}
