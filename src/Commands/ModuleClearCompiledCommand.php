<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\ModuleManifest;

class ModuleClearCompiledCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:clear-compiled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the module compiled class file';

    public function handle(ModuleManifest $manifest): void
    {
        if (is_file($manifest->manifestPath)) {
            @unlink($manifest->manifestPath);
        }

        $this->components->info('Compiled module files removed successfully.');
    }
}
