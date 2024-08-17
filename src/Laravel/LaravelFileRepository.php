<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function module(...$args): Module
    {
        return new Module(...$args);
    }
}
