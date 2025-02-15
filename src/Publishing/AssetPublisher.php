<?php

namespace Nwidart\Modules\Publishing;

use Nwidart\Modules\Support\Config\GenerateConfigReader;

class AssetPublisher extends Publisher
{
    /**
     * Determine whether the result message will shown in the console.
     */
    protected bool $showMessage = false;

    /**
     * Get destination path.
     */
    public function getDestinationPath(): string
    {
        return $this->repository->assetPath($this->module->getLowerName());
    }

    /**
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->getModule()->getExtraPath(
            GenerateConfigReader::read('assets')->getPath()
        );
    }
}
