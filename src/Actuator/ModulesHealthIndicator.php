<?php

namespace Nwidart\Modules\Actuator;

use Nwidart\Actuator\Health\Health;
use Nwidart\Actuator\Health\HealthIndicator;
use Nwidart\Modules\Activators\FileActivator;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Support\BrokenModules;

/**
 * Reports the modules for the nwidart/laravel-actuator health endpoint.
 *
 * Lists every module with its status. The health is DOWN when a module is enabled in the
 * status store but not found on disk, otherwise UP. A module the repository scanned counts
 * as found only while its module.json exists, so a folder removed after the scan (in a
 * long-lived process) is noticed with any activator: listed with an error when enabled,
 * left out when disabled. Only the FileActivator exposes its stored statuses, so with
 * another activator a module the repository never scanned is not reported.
 *
 * With the 'skip_broken_modules' config, a module skipped because it is broken (invalid
 * module.json, provider class not found) is listed with the reason as its error, and the
 * health is DOWN, with any repository. A module with an invalid module.json has no readable
 * name: it is listed under its folder name, or under its directory relative to the base path
 * when a module with that name is already listed.
 */
class ModulesHealthIndicator implements HealthIndicator
{
    private const MISSING_ERROR = 'Enabled in the status store, not found on disk.';

    private readonly BrokenModules $brokenModules;

    public function __construct(
        private readonly RepositoryInterface $modules,
        private readonly ActivatorInterface $activator,
        ?BrokenModules $brokenModules = null,
    ) {
        $this->brokenModules = $brokenModules ?? app(BrokenModules::class);
    }

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

        // Only enabled modules are checked for missing providers.
        foreach ($this->brokenModules->missingProviders() as $name => $reason) {
            $known[strtolower($name)] = true;
            $details[$name] = ['enabled' => true, 'error' => $reason];
            $down = true;
        }

        foreach ($this->brokenModules->invalidJson(array_keys($details)) as $label => $module) {
            $known[strtolower($module['folder'])] = true;
            $details[$label] = ['enabled' => $this->brokenModuleEnabled($module['folder']), 'error' => $module['reason']];
            $down = true;
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
     * Get the stored status of a module with an invalid module.json, or true when it is not known.
     *
     * Its module.json cannot be read, so its status is looked up by its folder name.
     */
    private function brokenModuleEnabled(string $name): bool
    {
        if ($this->activator instanceof FileActivator) {
            return array_change_key_case($this->activator->getStatuses())[strtolower($name)] ?? true;
        }

        return ! $this->activator->hasStatus($name, false);
    }

    /**
     * @return array<string, bool>
     */
    private function storedStatuses(): array
    {
        return $this->activator instanceof FileActivator ? $this->activator->getStatuses() : [];
    }
}
