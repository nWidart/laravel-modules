<?php

namespace Nwidart\Modules\Lumen;

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
