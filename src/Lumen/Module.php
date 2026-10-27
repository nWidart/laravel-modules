<?php

namespace Nwidart\Modules\Lumen;

use Illuminate\Support\Str;
use Nwidart\Modules\Module as BaseModule;

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
