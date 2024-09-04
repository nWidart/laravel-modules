<?php

namespace Nwidart\Modules\Commands\Actions;

use Nwidart\Modules\Commands\BaseCommand;

class UpdateCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dependencies for the specified module or for all modules.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $this->laravel['modules']->update($module);
        });
    }

    public function getInfo(): ?string
    {
        return 'Updating Module ...';
    }
}
