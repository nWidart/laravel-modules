<?php

namespace Nwidart\Modules\Commands;

use Symfony\Component\Console\Input\InputOption;

class MigrateRefreshCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback & re-migrate the modules migrations.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Refreshing Migration {$module->getName()} module", function () use ($module) {
            $this->call('module:migrate-reset', [
                'module'     => $module->getStudlyName(),
                '--database' => $this->option('database'),
                '--force'    => $this->option('force'),
            ]);

            $this->call('module:migrate', [
                'module'     => $module->getStudlyName(),
                '--database' => $this->option('database'),
                '--force'    => $this->option('force'),
            ]);

            if ($this->option('seed')) {
                $this->call('module:seed', [
                    'module' => $module->getStudlyName(),
                ]);
            }
        });

    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
        ];
    }
}
