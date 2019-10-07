<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\DatabaseRepository;

class LaravelDatabaseRepository extends DatabaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args)
    {
        return new Module(...$args);
    }
}
