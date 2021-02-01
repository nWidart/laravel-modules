<?php

namespace Nwidart\Modules\Contracts;

interface DatabaseRepositoryInterface
{
    public function create($params, $force = true, $isApi = true, $isPlain = true);

    public function getModuleType($isApi = true, $isPlain = true);

    public function migrateFileToDatabase($forceUpdate = false);
}
