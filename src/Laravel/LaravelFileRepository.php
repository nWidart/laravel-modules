<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\Contracts\ModuleInterface;
use Nwidart\Modules\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args): ModuleInterface
    {
        return new Module(...$args);
    }
}
