<?php

namespace Nwidart\Modules\Traits;

use Illuminate\Support\Arr;
use Nwidart\Modules\Support\GeneratorRun;

/**
 * Resolves the Inertia frontend from the --vue, --react and --svelte options,
 * falling back to the modules.inertia.frontend config value.
 */
trait ResolvesInertiaFrontend
{
    /**
     * The frontend options, in the order the error message lists them.
     *
     * @var list<string>
     */
    private const INERTIA_FRONTENDS = ['vue', 'react', 'svelte'];

    /**
     * Get the Inertia frontend: the framework option given, else the config default.
     */
    protected function getInertiaFrontend(): string
    {
        foreach (self::INERTIA_FRONTENDS as $frontend) {
            if ($this->option($frontend)) {
                return $frontend;
            }
        }

        return config('modules.inertia.frontend', 'vue');
    }

    /**
     * Print an error and return true when more than one framework option is given.
     */
    protected function reportConflictingInertiaFrontends(): bool
    {
        $given = array_values(array_filter(
            self::INERTIA_FRONTENDS,
            fn (string $frontend) => (bool) $this->option($frontend),
        ));

        if (count($given) < 2) {
            return false;
        }

        $message = sprintf(
            'The %s options cannot be used together. Pass only one of --vue, --react or --svelte.',
            Arr::join(array_map(fn (string $frontend) => '--'.$frontend, $given), ', ', ' and '),
        );

        $this->components->error($message);

        GeneratorRun::current()?->addError($message);

        return true;
    }
}
