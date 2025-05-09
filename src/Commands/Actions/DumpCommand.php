<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;

class DumpCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump-autoload the specified module or for all module.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Generating for <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            chdir($module->path());

            passthru('composer dump -o -n -q');
        });
    }

    public function getInfo(): ?string
    {
        return 'Generating optimized autoload modules';
    }
}
