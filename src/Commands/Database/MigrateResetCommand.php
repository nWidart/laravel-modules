<?php

namespace Nwidart\Modules\Commands\Database;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Nwidart\Modules\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class MigrateResetCommand extends BaseCommand implements ConfirmableCommand
{
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
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $migrator = new Migrator($module, $this->getLaravel(), $this->option('subpath'));

        if (! $migrator->subpathExists()) {
            $this->components->error("Migration subpath [{$this->option('subpath')}] does not exist in module [{$module->getName()}].");
            $this->markAsFailed();

            return;
        }

        $database = $this->option('database');

        if (! empty($database)) {
            $migrator->setDatabase($database);
        }

        if ($this->option('pretend')) {
            $this->pretend($migrator->reset(pretend: true), $module->getName());

            return;
        }

        $migrated = $migrator->reset();

        if (count($migrated)) {
            foreach ($migrated as $migration) {
                $this->line("Rollback: <info>{$migration}</info>");
            }

            return;
        }

        $this->components->warn("Nothing to rollback on module <fg=cyan;options=bold>{$module->getName()}</>");
    }

    /**
     * Show the SQL each migration would run, without running it.
     *
     * @param  array<string, array<int, string>>  $queries
     */
    protected function pretend(array $queries, string $module): void
    {
        if (empty($queries)) {
            $this->components->warn("Nothing to rollback on module <fg=cyan;options=bold>{$module}</>");

            return;
        }

        foreach ($queries as $migration => $sql) {
            $this->components->twoColumnDetail($migration);
            $this->components->bulletList($sql);
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return null;
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
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'desc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['subpath', null, InputOption::VALUE_OPTIONAL, 'A migrations subdirectory, file, or glob pattern, relative to the module migrations directory'],
        ];
    }
}
