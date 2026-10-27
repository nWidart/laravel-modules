<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;

class ModuleDeleteCommand extends BaseCommand implements ConfirmableCommand
{
    protected $name = 'module:delete';

    protected $description = 'Delete a module from the application';

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $this->components->task("Deleting <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $module->delete();
        });

        // The repository keeps its scan for the application lifetime: rescan without the deleted module.
        $repository = $this->moduleRepository();

        if (method_exists($repository, 'resetModules')) {
            $repository->resetModules();
        }
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'deleting module ...';
    }

    #[\Override]
    public function getConfirmableLabel(): string
    {
        return 'Warning: Do you want to remove the module?';
    }

    #[\Override]
    public function getConfirmableCallback(): \Closure|bool|null
    {
        return true;
    }
}
