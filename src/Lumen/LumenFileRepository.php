<?php

namespace Nwidart\Modules\Lumen;

use Nwidart\Modules\Contracts\ModuleInterface;
use Nwidart\Modules\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args): ModuleInterface
    {
        return new Module(...$args);
    }
}
