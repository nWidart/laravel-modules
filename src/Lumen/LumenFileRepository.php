<?php

namespace Nwidart\Modules\Lumen;

use Illuminate\Container\Container;
use Nwidart\Modules\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(Container $app, string $name, ?string $path = null): Module
    {
        return new Module($app, $name, $path);
    }
}
