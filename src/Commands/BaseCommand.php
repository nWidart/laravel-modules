<?php

namespace Nwidart\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Collection;
use Laravel\Prompts\Exceptions\NonInteractiveValidationException;
use Nwidart\Modules\Contracts\ConfirmableCommand;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Module;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multisearch;

abstract class BaseCommand extends Command implements PromptsForMissingInput
{
    use ConfirmableTrait;
    use InteractsWithModuleRepository;
    use Prohibitable;

    public const ALL = 'All';

    /**
     * Whether the action failed for at least one module during this run.
     */
    private bool $hasFailures = false;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->getDefinition()->addOption(
            option: new InputOption(
                name: strtolower(self::ALL),
                shortcut: 'a',
                mode: InputOption::VALUE_NONE,
                description: 'Check all Modules',
            )
        );

        $this->getDefinition()->addArgument(
            argument: new InputArgument(
                name: 'module',
                mode: InputArgument::IS_ARRAY,
                description: 'The name of module will be used.',
            )
        );

        if ($this instanceof ConfirmableCommand) {
            $this->configureConfirmable();
        }
    }

    /**
     * Run the action for one module.
     *
     * Return false or a non-zero exit code (or call markAsFailed()) when the action
     * failed for this module: the remaining modules still run, and the command exits
     * with a failure status.
     *
     * @param  string  $name
     * @return mixed
     */
    abstract public function executeAction($name);

    public function getInfo(): ?string
    {
        return null;
    }

    public function getConfirmableLabel(): ?string
    {
        return 'Application In Production';
    }

    public function getConfirmableCallback(): \Closure|bool|null
    {
        return null;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! $this->hasModulesToRun()) {
            return Command::FAILURE;
        }

        $this->validateModules();

        if (! $this->canProceed()) {
            return Command::FAILURE;
        }

        $this->executeModules();

        return $this->hasFailures() ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Check that a module or --all was given. Without interaction there is no prompt,
     * so an empty module argument would otherwise silently do nothing.
     */
    protected function hasModulesToRun(): bool
    {
        if ($this->option(strtolower(self::ALL)) || ! empty($this->argument('module'))) {
            return true;
        }

        $this->components->error('No module given. Pass a module name or --all.');

        return false;
    }

    /**
     * Check that every given module exists before any action runs, so a misspelled
     * name does not fail halfway, after the modules before it were processed.
     *
     * @throws ModuleNotFoundException
     */
    protected function validateModules(): void
    {
        foreach ((array) $this->argument('module') as $module) {
            $this->getModuleModel($module);
        }
    }

    /**
     * Mark the current run as failed, so the command exits with a failure status.
     */
    protected function markAsFailed(): void
    {
        $this->hasFailures = true;
    }

    /**
     * Whether the action failed for at least one module during this run.
     */
    protected function hasFailures(): bool
    {
        return $this->hasFailures;
    }

    /**
     * Run the prohibition check and the production confirmation of a confirmable command.
     */
    protected function canProceed(): bool
    {
        if (! $this instanceof ConfirmableCommand) {
            return true;
        }

        return ! $this->isProhibited()
            && $this->confirmToProceed($this->getConfirmableLabel(), $this->getConfirmableCallback());
    }

    /**
     * Run the action for each of the given modules.
     */
    protected function executeModules(): void
    {
        if (! is_null($info = $this->getInfo())) {
            $this->components->info($info);
        }

        $modules = (array) $this->argument('module');

        foreach ($modules as $module) {
            $result = $this->executeAction($module);

            if ($result === false || (is_int($result) && $result !== Command::SUCCESS)) {
                $this->markAsFailed();
            }
        }
    }

    /**
     * Resolve --all into the module argument. This runs for every input, unlike
     * promptForMissingArguments(), which is skipped with --no-interaction or without a TTY.
     */
    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->hasFailures = false;

        if ($input->getOption(strtolower(self::ALL))) {
            $input->setArgument('module', $this->getModuleNames($input));
        }
    }

    #[\Override]
    protected function promptForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        // --all was already resolved in initialize()
        if ($input->getOption(strtolower(self::ALL)) || ! empty($input->getArgument('module'))) {
            return;
        }

        $modules = $this->getModuleNames($input);

        try {
            $selected_item = $this->promptForModules($modules);
        } catch (NonInteractiveValidationException) {
            // Prompts cannot ask without a TTY, even when --no-interaction is not given.
            // handle() then reports the missing module.
            return;
        }

        $input->setArgument(
            'module',
            value: in_array(self::ALL, $selected_item)
                ? $modules
                : $selected_item
        );
    }

    /**
     * Ask which modules to use.
     *
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    private function promptForModules(array $modules): array
    {
        return multisearch(
            label: 'Select Modules',
            options: function (string $search_value) use ($modules) {
                return collect([
                    self::ALL,
                    ...$modules,
                ])->when(strlen($search_value) > 0, function (Collection &$modules) use ($search_value) {
                    return $modules->filter(fn ($item) => str_contains(strtolower($item), strtolower($search_value)));
                })->values()->toArray();
            },
            required: 'You must select at least one module',
        );
    }

    /**
     * Get the names of the modules that "all" stands for. Commands with a --direction
     * option get the enabled modules sorted by priority in that direction.
     *
     * @return array<int, string>
     */
    protected function getModuleNames(InputInterface $input): array
    {
        if (! $this->getDefinition()->hasOption('direction')) {
            return array_keys($this->moduleRepository()->all());
        }

        $direction = $input->getOption('direction')
            ?? $this->getDefinition()->getOption('direction')->getDefault();

        return array_keys($this->moduleRepository()->getOrdered((string) $direction));
    }

    /**
     * @param  Module|string  $name
     * @return Module
     */
    protected function getModuleModel($name)
    {
        return $name instanceof Module
            ? $name
            : $this->moduleRepository()->findOrFail($name);
    }

    private function configureConfirmable(): void
    {
        $this->getDefinition()
            ->addOption(
                option: new InputOption(
                    name: 'force',
                    shortcut: null,
                    mode: InputOption::VALUE_NONE,
                    description: 'Force the operation to run without confirmation.',
                )
            );
    }
}
