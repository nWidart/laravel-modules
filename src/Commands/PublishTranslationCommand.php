<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Nwidart\Modules\Publishing\LangPublisher;
use Symfony\Component\Console\Input\InputArgument;

class PublishTranslationCommand extends Command
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
     * Execute the console command.
     */
    public function handle()
    {
        if ($name = $this->argument('module')) {
            $this->publish($name);

            return;
        }

        $this->publishAll();
    }

    /**
     * Publish assets from all modules.
     */
    public function publishAll()
    {
        foreach ($this->laravel['modules']->enabled() as $module) {
            $this->publish($module);
        }
    }

    /**
     * Publish assets from the specified module.
     *
     * @param string $name
     */
    public function publish($name)
    {
        if ($name instanceof Module) {
            $module = $name;
        } else {
            $module = $this->laravel['modules']->findOrFail($name);
        }

        with(new LangPublisher($module))
            ->setRepository($this->laravel['modules'])
            ->setConsole($this)
            ->publish();

        $this->line("<info>Published</info>: {$module->getStudlyName()}");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }
}
