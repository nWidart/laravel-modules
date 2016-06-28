<?php

namespace Nwidart\Modules\Publishing;

class MigrationPublisher extends AssetPublisher
{
    /**
     * Get destination path.
     *
     * @return string
     */
    public function getDestinationPath()
    {
        return $this->repository->config('paths.migration');
    }

    /**
     * Get source path.
     *
     * @return string
     */
    public function getSourcePath()
    {
        return $this->getModule()->getExtraPath($this->repository->config('paths.generator.migration'));
    }
}
