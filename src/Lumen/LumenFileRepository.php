<?php

namespace Nwidart\Modules\Lumen;

use Nwidart\Modules\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function module(...$args): Module
    {
        return new Module(...$args);
    }
}
