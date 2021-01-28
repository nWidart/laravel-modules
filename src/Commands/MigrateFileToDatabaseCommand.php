<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;

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
        $this->laravel['modules']->migrateFileToDatabase();
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
        return [];
    }
}
