<?php

namespace Nwidart\Modules\Publishing;

use Nwidart\Modules\Migrations\Migrator;

class MigrationPublisher extends AssetPublisher
{
    /**
     * Migrator
     */
    private Migrator $migrator;

    /**
     * Published migrations may be edited by the app, so existing files are kept unless forced.
     */
    protected bool $force = false;

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
     */
    #[\Override]
    public function getDestinationPath(): string
    {
        return $this->getRepository()->config('paths.migration');
    }

    /**
     * Get source path.
     */
    #[\Override]
    public function getSourcePath(): string
    {
        return $this->migrator->getPath();
    }
}
