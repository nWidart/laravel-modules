<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args): \Nwidart\Modules\Module
    {
        return new Module(...$args);
    }
}
