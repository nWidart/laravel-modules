<?php

declare(strict_types=1);

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;

class LaravelModulesV6Migrator extends Command
{
    use InteractsWithModuleRepository;

    protected $name = 'module:v6:migrate';

    protected $description = 'Migrate laravel-modules v5 modules statuses to v6.';

    public function handle(): int
    {
        $moduleStatuses = [];
        $modules = $this->moduleRepository();

        $modules = $modules->all();
        /** @var Module $module */
        foreach ($modules as $module) {
            if ($module->json()->get('active') === 1) {
                $module->enable();
                $moduleStatuses[] = [$module->getName(), 'Enabled'];
            }
            if ($module->json()->get('active') === 0) {
                $module->disable();
                $moduleStatuses[] = [$module->getName(), 'Disabled'];
            }
        }
        $this->info('All modules have been migrated.');
        $this->table(['Module name', 'Status'], $moduleStatuses);

        return 0;
    }
}
