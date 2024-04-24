<?php

namespace Nwidart\Modules\Commands\Database;

use Illuminate\Console\Command;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrateFreshCommand extends Command
{
    use ModuleCommandTrait;

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = $this->argument('module');

        if ($module && !$this->getModuleName()) {
            $this->error("Module [$module] does not exists.");

            return E_ERROR;
        }

        $this->call('module:migrate-refresh', [
            'module' => $this->getModuleName(),
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
            '--seed' => $this->option('seed'),
        ]);

        return 0;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'],
        ];
    }

    public function getModuleName()
    {
        $module = $this->argument('module');

        if (!$module) {
            return null;
        }

        $module = app('modules')->find($module);

        return $module ? $module->getStudlyName() : null;
    }
}
