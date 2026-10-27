<?php

namespace Nwidart\Modules\Commands\Actions;

use Illuminate\Console\View\TaskResult;
use Nwidart\Modules\Commands\BaseCommand;

class DumpCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:dump';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump-autoload the specified module or for all module.';

    /**
     * @param  string  $name
     */
    #[\Override]
    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Generating for <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            if ($this->dumpAutoload($module->getPath()) === self::SUCCESS) {
                return TaskResult::Success->value;
            }

            $this->markAsFailed();

            return TaskResult::Failure->value;
        });
    }

    /**
     * Run composer dump-autoload in the given directory and return its exit code.
     * The working directory is restored afterwards.
     */
    protected function dumpAutoload(string $path): int
    {
        $cwd = getcwd();

        try {
            if (! chdir($path)) {
                return self::FAILURE;
            }

            return $this->runComposerDump();
        } finally {
            if ($cwd !== false) {
                chdir($cwd);
            }
        }
    }

    /**
     * Run composer dump-autoload in the current working directory and return its exit code.
     */
    protected function runComposerDump(): int
    {
        $code = self::FAILURE;

        passthru('composer dump -o -n -q', $code);

        return $code;
    }

    #[\Override]
    public function getInfo(): ?string
    {
        return 'Generating optimized autoload modules';
    }
}
