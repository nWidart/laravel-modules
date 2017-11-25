<?php

namespace Nwidart\Modules\Laravel;

use Nwidart\Modules\Json;
use Nwidart\Modules\Repository as BaseRepository;

class Repository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args)
    {
        return new Module(...$args);
    }
}
