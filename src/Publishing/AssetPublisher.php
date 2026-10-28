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
     * Assets are build output, so publishing refreshes them.
     */
    protected bool $force = true;

    /**
     * Get destination path.
     */
    #[\Override]
    public function getDestinationPath(): string
    {
        return $this->getRepository()->assetPath($this->module->getLowerName());
    }

    /**
     * Get source path.
     */
    #[\Override]
    public function getSourcePath(): string
    {
        return $this->getModule()->getExtraPath(
            GenerateConfigReader::read('assets')->getPath()
        );
    }
}
