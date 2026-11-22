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
 * status store but not found on disk, otherwise UP. Only the FileActivator exposes its
 * stored statuses, so with another activator the modules are listed and the health is UP.
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
        $found = [];

        foreach ($this->modules->all() as $module) {
            $details[$module->getName()] = ['enabled' => $this->activator->hasStatus($module, true)];
            $found[$module->getLowerName()] = true;
        }

        $missing = [];

        foreach ($this->storedStatuses() as $name => $enabled) {
            // The repository finds modules by lower case name.
            if ($enabled === true && ! isset($found[strtolower($name)])) {
                $missing[$name] = ['enabled' => true, 'error' => self::MISSING_ERROR];
            }
        }

        $details = ['modules' => $details + $missing];

        return $missing === [] ? Health::up($details) : Health::down($details);
    }

    /**
     * @return array<string, bool>
     */
    private function storedStatuses(): array
    {
        return $this->activator instanceof FileActivator ? $this->activator->getStatuses() : [];
    }
}
