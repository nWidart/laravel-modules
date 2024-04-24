<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\multiselect;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command implements PromptsForMissingInput
{
    public const ALL = 'All';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->getDefinition()->addOption(new InputOption(
            strtolower(self::ALL),
            'a',
            InputOption::VALUE_NONE,
            'Check all Modules',
        ));

        $this->getDefinition()->addArgument(new InputArgument(
            'module',
            InputArgument::IS_ARRAY,
            'The name of module will be used.',
        ));
    }

    abstract public function executeAction($name);

    public function getInfo(): string|null
    {
        return null;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! is_null($info = $this->getInfo())) {
            $this->components->info($info);
        }

        $modules = (array) $this->argument('module');

        foreach ($modules as $module) {
            $this->executeAction($module);
        }
    }

    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $modules = $this->hasOption('direction')
            ? array_keys($this->laravel['modules']->getOrdered($input->hasOption('direction')))
            : array_keys($this->laravel['modules']->all());

        if ($input->getOption(strtolower(self::ALL))) {
            $input->setArgument('module', $modules);

            return;
        }

        if (! empty($input->getArgument('module'))) {
            return;
        }

        $selected_item = multiselect(
            label   : 'Select Modules',
            options : [
                self::ALL,
                ...$modules,
            ],
            required: 'You must select at least one module',
        );

        $input->setArgument(
            'module',
            value: in_array(self::ALL, $selected_item)
                ? $modules
                : $selected_item
        );
    }

    protected function getModuleModel($name)
    {
        return $name instanceof \Nwidart\Modules\Module
            ? $name
            : $this->laravel['modules']->findOrFail($name);
    }

}
