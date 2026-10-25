<?php

namespace Nwidart\Modules\Exceptions;

/** @phpstan-consistent-constructor */
class InvalidActivatorClass extends \Exception
{
    /**
     * @return static
     */
    public static function missingConfig()
    {
        return new static("You don't have a valid activator configuration class. This might be due to your config being out of date. \n Run php artisan vendor:publish --provider=\"Nwidart\Modules\LaravelModulesServiceProvider\" --force to publish the up to date configuration");
    }

    public static function unknownActivator(mixed $activator): self
    {
        $name = is_scalar($activator) ? (string) $activator : get_debug_type($activator);

        return new self("The modules activator [{$name}] is not defined. Set modules.activator to a key of modules.activators in config/modules.php.");
    }

    public static function classNotFound(string $activator, string $class): self
    {
        return new self("The class [{$class}] of the modules activator [{$activator}] does not exist. Check modules.activators.{$activator}.class in config/modules.php.");
    }
}
