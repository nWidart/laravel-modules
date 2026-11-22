<?php

namespace Nwidart\Modules\Actuator;

use Nwidart\Actuator\Health\Health;
use Nwidart\Actuator\Health\HealthIndicator;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;

/**
 * Reports the modules for the nwidart/laravel-actuator health endpoint.
 *
 * Lists every module with its status. The health is DOWN when a module is enabled in the
 * status store but not found on disk, otherwise UP. A module the repository scanned counts
 * as found only while its module.json exists, so a folder removed after the scan (in a
 * long-lived process) is noticed with any activator: listed with an error when enabled,
 * left out when disabled. Only the FileActivator exposes its stored statuses, so with
 * another activator a module the repository never scanned is not reported.
 */
class ModulesHealthIndicator implements HealthIndicator
{
    private const MISSING_ERROR = 'Enabled in the status store, not found on disk.';

    public function __construct(
        private readonly RepositoryInterface $modules,
        private readonly ActivatorInterface $activator,
    ) {}

    #[\Override]
    public function health(): Health
    {
        $details = [];
        $known = [];
        $down = false;

        foreach ($this->modules->all() as $module) {
            $known[$module->getLowerName()] = true;
            $enabled = $this->activator->hasStatus($module, true);

            // In a long-lived process the scan is cached, so check that the module is still on disk.
            if (! is_file($module->getExtraPath('module.json'))) {
                if ($enabled) {
                    $details[$module->getName()] = ['enabled' => true, 'error' => self::MISSING_ERROR];
                    $down = true;
                }

                continue;
            }

            $details[$module->getName()] = ['enabled' => $enabled];
        }

        foreach ($this->storedStatuses() as $name => $enabled) {
            // The repository finds modules by lower case name.
            if ($enabled === true && ! isset($known[strtolower($name)])) {
                $details[$name] = ['enabled' => true, 'error' => self::MISSING_ERROR];
                $down = true;
            }
        }

        $details = ['modules' => $details];

        return $down ? Health::down($details) : Health::up($details);
    }

    /**
     * @return array<string, bool>
     */
    private function storedStatuses(): array
    {
        return $this->activator instanceof FileActivator ? $this->activator->getStatuses() : [];
    }
}
