<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Contracts\ConfirmableCommand;

class ModuleDeleteCommand extends BaseCommand implements ConfirmableCommand
{
    use ConfirmableTrait, Prohibitable;

    protected $name        = 'module:delete';
    protected $description = 'Delete a module from the application';

    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed('Warning: Do you want to remove the module?', fn () => TRUE)) {
            return 1;
        }

        parent::handle();
    }

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

}
