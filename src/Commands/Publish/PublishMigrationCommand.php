<?php

namespace Nwidart\Modules\Commands\Publish;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Migrations\Migrator;
use Nwidart\Modules\Publishing\MigrationPublisher;
use Symfony\Component\Console\Input\InputOption;

class PublishMigrationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:publish-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish a module's migrations to the application";

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $publisher = (new MigrationPublisher(new Migrator($module, $this->getLaravel())))
            ->setRepository($this->moduleRepository())
            ->setConsole($this)
            ->setForce((bool) $this->option('force'));

        $this->components->task("Publishing Migration <fg=cyan;options=bold>{$module->getName()}</> Module", fn () => $publisher->publish());

        $publisher->reportSkippedFiles();
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Publishing module migrations ...';
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite migrations that were already published'],
        ];
    }
}
