<?php

namespace Nwidart\Modules\Laravel;

use Illuminate\Container\Container;
use Nwidart\Modules\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(Container $app, string $name, string $path): Module
    {
        return new Module($app, $name, $path);
    }
}
