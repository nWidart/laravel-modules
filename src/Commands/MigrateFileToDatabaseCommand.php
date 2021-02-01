<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MigrateFileToDatabaseCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-to-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all modules to database management.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('modules.database_management.enabled')) {
            $this->info('This feature only works when database management is on.');

            return false;
        }
        $this->laravel['modules']->migrateFileToDatabase($this->option('force'));
        $this->info('Migrated.');

        return 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to update database.'],
        ];
    }
}
