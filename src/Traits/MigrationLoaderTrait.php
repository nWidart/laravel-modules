<?php

namespace Nwidart\Modules\Traits;

trait MigrationLoaderTrait
{
    /**
     * Include all migrations files from the specified module.
     */
    protected function loadMigrationFiles(string $module)
    {
        $path = $this->laravel['modules']->getModulePath($module).$this->getMigrationGeneratorPath();

        $files = $this->laravel['files']->glob($path.'/*_*.php');

        foreach ($files as $file) {
            $this->laravel['files']->requireOnce($file);
        }
    }

    /**
     * Get migration generator path.
     */
    protected function getMigrationGeneratorPath(): string
    {
        return $this->laravel['modules']->config('paths.generator.migration');
    }
}
