<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multiselect;

abstract class BaseCommand extends Command implements PromptsForMissingInput
{
    use ConfirmableTrait;
    use Prohibitable;

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

        if ($this instanceof ConfirmableCommand) {
            $this->configureConfirmable();
        }
    }

    abstract public function executeAction($name);

    public function getInfo(): ?string
    {
        return null;
    }

    public function getConfirmableLabel(): ?string
    {
        return 'Warning';
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this instanceof ConfirmableCommand) {
            if ($this->isProhibited() ||
                ! $this->confirmToProceed($this->getConfirmableLabel(), fn () => true)) {
                return 1;
            }
        }

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

    private function configureConfirmable(): void
    {
        $this->getDefinition()
            ->addOption(new InputOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force the operation to run without confirmation.',
            ));
    }
}
