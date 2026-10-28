<?php

namespace Nwidart\Modules\Commands\Publish;

use Nwidart\Modules\Commands\BaseCommand;
use Nwidart\Modules\Publishing\LangPublisher;
use Symfony\Component\Console\Input\InputOption;

class PublishTranslationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:publish-translation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a module\'s translations to the application';

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $publisher = (new LangPublisher($module))
            ->setRepository($this->moduleRepository())
            ->setConsole($this)
            ->setForce((bool) $this->option('force'));

        $this->components->task("Publishing Translations <fg=cyan;options=bold>{$module->getName()}</> Module", fn () => $publisher->publish());

        $publisher->reportSkippedFiles();
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Publishing module translations ...';
    }

    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Overwrite translations that were already published'],
        ];
    }
}
