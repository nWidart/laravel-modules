<?php

namespace Nwidart\Modules\Publishing;

use Nwidart\Modules\Support\Config\GenerateConfigReader;

class LangPublisher extends Publisher
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
        $name = $this->module->getLowerName();

        return base_path("resources/lang/{$name}");
    }

    /**
     * Get source path.
     */
    public function getSourcePath(): string
    {
        return $this->getModule()->getExtraPath(
            GenerateConfigReader::read('lang')->getPath()
        );
    }
}
