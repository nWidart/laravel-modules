<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;

class UnUseCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:unuse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forget the used module with module:use';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Forget Using <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $this->laravel['modules']->forgetUsed($module);
        });
    }

    public function getInfo(): ?string
    {
        return 'Forget Using Module ...';
    }
}
