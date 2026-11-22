<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\Command;
use Nwidart\Modules\Exceptions\ModuleNotFoundException;
use Nwidart\Modules\Traits\InteractsWithModuleRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UnUseCommand extends Command
{
    use InteractsWithModuleRepository;

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
    protected $description = 'Forget the module used with module:use';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $repository = $this->moduleRepository();

        try {
            $used = $repository->getUsedNow();
        } catch (ModuleNotFoundException) {
            $used = null;
        }

        if ($used === null) {
            // still delete the file: it can hold the name of a module that no longer exists
            $repository->forgetUsed();

            $this->components->info('No module is in use.');

            return self::SUCCESS;
        }

        $this->components->task("Forget Using <fg=cyan;options=bold>{$used}</> Module", function () use ($repository) {
            $repository->forgetUsed();
        });

        return self::SUCCESS;
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[\Override]
    protected function getArguments()
    {
        return [
            ['module', InputArgument::IS_ARRAY, 'Ignored: the used module is forgotten, whatever the name. Kept for backward compatibility.'],
        ];
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
            ['all', 'a', InputOption::VALUE_NONE, 'Ignored. Kept for backward compatibility.'],
        ];
    }
}
