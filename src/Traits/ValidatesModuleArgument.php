<?php

namespace Nwidart\Modules\Traits;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

/**
 * Checks the module argument and --all option of an artisan command before it runs.
 *
 * The command needs an array "module" argument, an "--all" option and the
 * InteractsWithModuleRepository trait.
 */
trait ValidatesModuleArgument
{
    /**
     * Get the module repository.
     *
     * @return RepositoryInterface
     */
    abstract protected function moduleRepository();

    /**
     * Check that a module or --all was given. Without interaction there is no prompt,
     * so an empty module argument would otherwise silently do nothing.
     */
    protected function hasModulesToRun(): bool
    {
        if ($this->option('all') || ! empty($this->argument('module'))) {
            return true;
        }

        $this->components->error('No module given. Pass a module name or --all.');

        return false;
    }

    /**
     * Check that every given module exists before any action runs, so a misspelled
     * name does not fail halfway, after the modules before it were processed.
     * Prints one error line that names every unknown module.
     *
     * @return bool Whether every given module exists.
     */
    protected function validateModules(): bool
    {
        $unknown = collect($this->moduleNamesToValidate())
            ->reject(fn ($module) => $module instanceof Module || $this->moduleRepository()->find((string) $module) !== null)
            ->values();

        if ($unknown->isEmpty()) {
            return true;
        }

        $this->components->error($unknown->count() === 1
            ? "Module [{$unknown->first()}] does not exist."
            : 'Modules ['.$unknown->implode(', ').'] do not exist.');

        return false;
    }

    /**
     * Get the given modules that must exist: the module argument.
     *
     * @return array<int, mixed>
     */
    protected function moduleNamesToValidate(): array
    {
        return (array) $this->argument('module');
    }
}
