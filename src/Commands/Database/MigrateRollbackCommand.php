<?php

namespace Nwidart\Modules\Commands\Database;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Traits\MigrationLoaderTrait;
use Symfony\Component\Console\Input\InputOption;

class MigrateRollbackCommand extends BaseCommand
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

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $migrator = new Migrator($module, $this->getLaravel(), $this->option('subpath'));

        $database = $this->option('database');

        if (! empty($database)) {
            $migrator->setDatabase($database);
        }

        $migrated = $migrator->rollback();

        if (count($migrated)) {
            foreach ($migrated as $migration) {
                $this->components->task("Rollback: <info>{$migration}</info>",);
            }

            return;
        }

        $this->components->warn("Nothing to rollback on module <fg=cyan;options=bold>{$module->getName()}</>");

    }

    public function getInfo(): string|null
    {
        return NULL;
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
            ['database', NULL, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', NULL, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', NULL, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['subpath', NULL, InputOption::VALUE_OPTIONAL, 'Indicate a subpath for modules specific migration file'],
        ];
    }
}
