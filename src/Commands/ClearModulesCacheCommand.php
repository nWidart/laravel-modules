<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Traits\CanClearModulesCache;

class ClearModulesCacheCommand extends Command
{
    use CanClearModulesCache;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:clear-cache {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the modules cache';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');
        $this->clearCache($force);
        $this->info('The modules cache has been cleared.');
    }
}
