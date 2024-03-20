<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;

class EnableCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable the specified module.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $status = $module->isDisabled()
            ? '<fg=red;options=bold>Disabled</>'
            : '<fg=green;options=bold>Enabled</>';

        $this->components->task("Enabling <fg=cyan;options=bold>{$module->getName()}</> Module, old status: $status", function () use ($module) {
            $module->enable();
        });
    }

    public function getInfo(): string|null
    {
        return 'Disabling module ...';
    }
}
