<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;

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

    public function getInfo(): string|null
    {
        return 'Forget Using Module ...';
    }
}
