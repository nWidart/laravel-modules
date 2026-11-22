<?php

namespace Nwidart\Modules\Actuator;

use Composer\InstalledVersions;
use Nwidart\Actuator\Endpoints\Attributes\ReadOperation;
use Nwidart\Actuator\Endpoints\Attributes\Selector;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

/**
 * The "modules" endpoint of nwidart/laravel-actuator: the modules of the app, enabled and disabled.
 *
 * Registered by LaravelModulesServiceProvider when the actuator is installed. Read-only, and it
 * exposes a fixed set of fields: no paths, no other module.json keys, no composer.json data.
 *
 * @phpstan-type ModuleData array{name: string, enabled: bool, version: string|null, description: string, priority: int, providers: list<string>}
 */
final class ModulesEndpoint
{
    public function __construct(private readonly RepositoryInterface $modules) {}

    /**
     * GET /actuator/modules: the package version and every module, sorted by name.
     *
     * @return array{package: array{name: string, version: string|null}, modules: list<ModuleData>}
     */
    #[ReadOperation]
    public function modules(): array
    {
        $modules = array_values($this->modules->all());

        usort($modules, fn (Module $a, Module $b): int => strcasecmp($a->getName(), $b->getName()));

        return [
            'package' => [
                'name' => 'nwidart/laravel-modules',
                'version' => InstalledVersions::getPrettyVersion('nwidart/laravel-modules'),
            ],
            'modules' => array_map($this->describe(...), $modules),
        ];
    }

    /**
     * GET /actuator/modules/{name}: one module, found like Module::find(). Null gives a 404.
     *
     * @return ModuleData|null
     */
    #[ReadOperation]
    public function module(#[Selector] string $name): ?array
    {
        $module = $this->modules->find($name);

        return $module === null ? null : $this->describe($module);
    }

    /**
     * @return ModuleData
     */
    private function describe(Module $module): array
    {
        $version = $module->get('version');
        $providers = $module->get('providers', []);

        return [
            'name' => $module->getName(),
            'enabled' => $module->isEnabled(),
            'version' => (is_string($version) || is_int($version) || is_float($version)) && (string) $version !== '' ? (string) $version : null,
            'description' => $module->getDescription(),
            'priority' => (int) $module->getPriority(),
            'providers' => is_array($providers) ? array_values(array_filter($providers, is_string(...))) : [],
        ];
    }
}
