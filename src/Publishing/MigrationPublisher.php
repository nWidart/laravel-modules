<?php

namespace Nwidart\Modules\Publishing;

use Nwidart\Modules\Migrations\Migrator;

class MigrationPublisher extends AssetPublisher
{
    /**
     * @var Migrator
     */
    private Migrator $migrator;

    /**
     * MigrationPublisher constructor.
     */
    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
        parent::__construct($migrator->getModule());
    }

    /**
     * Get destination path.
     *
     * @return string
     */
    public function getDestinationPath(): string
    {
        return $this->repository->config('paths.migration');
    }

    /**
     * Get source path.
     *
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->migrator->getPath();
    }
}
