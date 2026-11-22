<?php

namespace Nwidart\Modules\Lumen;

use Illuminate\Support\Str;
use Nwidart\Modules\Module as BaseModule;

/**
 * @deprecated Lumen is no longer supported. LumenModulesServiceProvider cannot register
 *             since v11.1.0, and Lumen has no release for Laravel 13. There is no
 *             replacement in the package: use Laravel with LaravelModulesServiceProvider,
 *             whose repository creates Nwidart\Modules\Laravel\Module. Will be removed in v14.
 */
class Module extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getCachedServicesPath(): string
    {
        return Str::replaceLast('services.php', $this->getSnakeName().'_module.php', $this->app->basePath('storage/app/').'services.php');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function registerProviders(): void
    {
        foreach ($this->get('providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function registerAliases(): void {}
}
