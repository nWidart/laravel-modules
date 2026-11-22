<?php

namespace Nwidart\Modules\Actuator;

use Illuminate\Contracts\Foundation\Application;
use Nwidart\Actuator\ActuatorServiceProvider;

/**
 * Adds health indicators to the nwidart/laravel-actuator config.
 *
 * Used by the package service provider (the "modules" indicator) and by module service
 * providers ($healthIndicators). Call it from boot(): every provider has registered by
 * then, so the actuator config is merged.
 *
 * @internal
 */
final class HealthIndicators
{
    /**
     * Add the indicators to actuator.health.indicators.
     *
     * Does nothing when the actuator service provider is not registered or when
     * modules.actuator.enabled is false (a missing key counts as true). An indicator
     * the application already defines under the same name is kept.
     *
     * @param  array<string, class-string|array{0: class-string, 1?: array<string, mixed>}>  $indicators
     */
    public static function register(Application $app, array $indicators): void
    {
        // ::class does not autoload the class, so this works without the actuator installed.
        if ($indicators === [] || $app->getProviders(ActuatorServiceProvider::class) === []) {
            return;
        }

        $config = $app->make('config');

        if (! (bool) ($config->get('modules.actuator.enabled') ?? true)) {
            return;
        }

        $registered = $config->get('actuator.health.indicators');

        // Write the whole array: indicator names may contain dots.
        $config->set('actuator.health.indicators', (is_array($registered) ? $registered : []) + $indicators);
    }
}
