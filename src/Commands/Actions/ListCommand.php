<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Nwidart\Modules\Support\BrokenModules;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends Command
{
    use InteractsWithModuleRepository;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

    /**
     * The status shown for a module skipped because it is broken.
     */
    private const BROKEN_STATUS = '<fg=red;options=bold>Broken</>';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->twoColumnDetail('<fg=gray>Status / Name</>', '<fg=gray>Path / priority</>');

        // getRows() scans the modules first, so a broken module found by that scan is listed too.
        $rows = $this->getRows();
        $missingProviders = $this->brokenModules()->missingProviders();

        foreach ($rows as $row) {
            $this->components->twoColumnDetail(
                "[{$row[1]}] {$row[0]}",
                $missingProviders[$row[0]] ?? "{$row[3]} [{$row[2]}]"
            );
        }

        // A module with an invalid module.json is not in the repository, so its status is unknown:
        // it is listed only when no --only filter is given.
        if ($this->option('only') === null) {
            $names = array_map(fn (Module $module) => $module->getName(), array_values($this->moduleRepository()->all()));

            foreach ($this->brokenModules()->invalidJson($names) as $label => $module) {
                $this->components->twoColumnDetail('['.self::BROKEN_STATUS."] {$label}", $module['reason']);
            }
        }

        return 0;
    }

    /**
     * Get table rows.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}> Name, status, priority and path of each module.
     */
    public function getRows()
    {
        $rows = [];
        $modules = $this->getModules();
        $missingProviders = $this->brokenModules()->missingProviders();

        /** @var Module $module */
        foreach ($modules as $module) {
            $rows[] = [
                $module->getName(),
                match (true) {
                    isset($missingProviders[$module->getName()]) => self::BROKEN_STATUS,
                    $module->isEnabled() => '<fg=green>Enabled</>',
                    default => '<fg=red>Disabled</>',
                },
                $module->getPriority(),
                $module->getPath(),
            ];
        }

        return $rows;
    }

    /**
     * Get the modules skipped because they are broken ('skip_broken_modules' config).
     */
    private function brokenModules(): BrokenModules
    {
        return $this->laravel->make(BrokenModules::class);
    }

    /**
     * Get the modules selected by the --only option.
     *
     * @return array<string, Module>
     */
    public function getModules()
    {
        return match ($this->option('only')) {
            'enabled' => $this->moduleRepository()->getByStatus(1),
            'disabled' => $this->moduleRepository()->getByStatus(0),
            'priority' => $this->moduleRepository()->getOrdered($this->option('direction')),
            default => $this->moduleRepository()->all(),
        };
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Types of modules will be displayed.', null],
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
        ];
    }
}
