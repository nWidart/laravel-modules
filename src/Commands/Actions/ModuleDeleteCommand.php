<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommandInterface;

class ModuleDeleteCommand extends BaseCommand implements ConfirmableCommandInterface
{
    protected $name        = 'module:delete';
    protected $description = 'Delete a module from the application';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $this->components->task("Deleting <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $module->delete();
        });
    }

    public function getInfo(): string|null
    {
        return 'deleting module ...';
    }

    public function getConfirmLabel(): string
    {
        return 'Do you want delete module?';
    }
}
