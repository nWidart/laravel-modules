<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\ModuleManifest;

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
        $this->components->info('Discovering modules');

        $manifest->build();

        collect($manifest->providersArray())
            ->map(fn ($provider) => preg_match('/Modules\\\\(.*?)\\\\/', $provider, $matches) ? $matches[1] : null)
            ->unique()
            ->each(fn ($description) => $this->components->task($description))
            ->whenNotEmpty(fn () => $this->newLine());
    }
}
