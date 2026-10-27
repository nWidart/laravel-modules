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
     *
     * Must match the path read by ModuleServiceProvider::registerTranslations().
     */
    #[\Override]
    public function getDestinationPath(): string
    {
        return lang_path('modules/'.$this->module->getLowerName());
    }

    /**
     * Get source path.
     */
    #[\Override]
    public function getSourcePath(): string
    {
        return $this->getModule()->getExtraPath(
            GenerateConfigReader::read('lang')->getPath()
        );
    }
}
