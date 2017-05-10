<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateRefreshCommand extends Command
{
    use ModuleCommandTrait;

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

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $this->call('module:migrate-reset', [
            'module' => $this->getModuleName(),
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
        ]);

        $this->call('module:migrate', [
            'module' => $this->getModuleName(),
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
        ]);

        if ($this->option('seed')) {
            $this->call('module:seed', [
                'module' => $this->getModuleName(),
            ]);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module will be used.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
        );
    }
}
