<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use Nwidart\Modules\FileRepository;
use Nwidart\Modules\Module;
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
        $broken = $this->getBrokenModules();

        $this->components->twoColumnDetail('<fg=gray>Status / Name</>', '<fg=gray>Path / priority</>');
        collect($this->getRows())->each(function ($row) use (&$broken) {
            if (isset($broken[$row[0]])) {
                $this->components->twoColumnDetail("[{$row[1]}] {$row[0]}", $broken[$row[0]]);
                unset($broken[$row[0]]);

                return;
            }

            $this->components->twoColumnDetail("[{$row[1]}] {$row[0]}", "{$row[3]} [{$row[2]}]");
        });

        // A module with an invalid module.json is not in the repository, so its status is unknown:
        // it is listed only when no --only filter is given.
        if ($this->option('only') === null) {
            foreach ($broken as $name => $reason) {
                $this->components->twoColumnDetail('['.self::BROKEN_STATUS."] {$name}", $reason);
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
        $broken = $this->getBrokenModules();

        /** @var Module $module */
        foreach ($this->getModules() as $module) {
            $rows[] = [
                $module->getName(),
                match (true) {
                    isset($broken[$module->getName()]) => self::BROKEN_STATUS,
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
     * Get the modules skipped because they are broken ('skip_broken_modules' config), with the reason for each.
     *
     * @return array<string, string>
     */
    private function getBrokenModules(): array
    {
        $repository = $this->moduleRepository();

        return $repository instanceof FileRepository ? $repository->getBrokenModules() : [];
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
