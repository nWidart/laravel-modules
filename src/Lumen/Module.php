<?php
declare(strict_types=1);

namespace Nwidart\Modules\Lumen;

use Illuminate\Support\Str;
use Nwidart\Modules\Module as BaseModule;

class Module extends BaseModule
{
    /**
     * {@inheritdoc}
     */
    public function getCachedServicesPath(): string
    {
        return Str::replaceLast('services.php', $this->getSnakeName() . '_module.php', $this->app->basePath('storage/app/') . 'services.php');
    }

    /**
     * {@inheritdoc}
     */
    public function registerProviders(): void
    {
        foreach ($this->get('providers', []) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerAliases(): void
    {
    }

    public function getLaravel()
    {
        // TODO: Implement getLaravel() method.
    }

    public function enabled(): bool
    {
        // TODO: Implement enabled() method.
    }

    public function disabled(): bool
    {
        // TODO: Implement disabled() method.
    }
}
