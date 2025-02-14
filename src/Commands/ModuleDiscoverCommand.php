<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\ModuleManifest;

/**
 * @deprecated This command is deprecated and will be removed in the next major version.
 */
class ModuleDiscoverCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create module compiled class file';

    public function handle(ModuleManifest $manifest): void
    {
        $this->components->warn('You may stop calling the `module:discover` command.');
    }
}
