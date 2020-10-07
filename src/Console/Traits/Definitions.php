<?php

namespace Nwidart\Modules\Console\Traits;

trait Definitions
{

    /**
     * Get modules
     *
     * @return object
     */
    public function getModules()
    {
        return $this->laravel['modules'];
    }

    /**
     * Get specified module
     *
     * @param string $moduleName
     * @return object
     */
    public function getModule(string $moduleName)
    {
        return $this->getModules()->findOrFail($moduleName ?: $this->getModuleName());
    }
}
