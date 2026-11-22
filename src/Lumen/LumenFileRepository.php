<?php

namespace Nwidart\Modules\Lumen;

use Illuminate\Container\Container;
use Nwidart\Modules\FileRepository;

/**
 * @deprecated Lumen is no longer supported. LumenModulesServiceProvider cannot register
 *             since v11.1.0, and Lumen has no release for Laravel 13. There is no
 *             replacement in the package: use Laravel with LaravelModulesServiceProvider,
 *             which binds Nwidart\Modules\Laravel\LaravelFileRepository. Will be removed in v14.
 */
class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function createModule(Container $app, string $name, ?string $path = null): Module
    {
        return new Module($app, $name, $path);
    }
}
