<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;

class ModuleDeleteCommand extends BaseCommand implements ConfirmableCommand
{
    protected $name = 'module:delete';

    protected $description = 'Delete a module from the application';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $this->components->task("Deleting <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $module->delete();
        });
    }

    public function getInfo(): ?string
    {
        return 'deleting module ...';
    }

    public function getConfirmableLabel(): string
    {
        return 'Warning: Do you want to remove the module?';
    }

    public function getConfirmableCallback(): \Closure|bool|null
    {
        return true;
    }
}
