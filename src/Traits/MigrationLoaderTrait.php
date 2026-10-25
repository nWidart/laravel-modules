<?php

namespace Nwidart\Modules\Traits;

/**
 * @deprecated Unused by the package. getMigrationGeneratorPath() returns the whole
 *             paths.generator.migration config array, so calling it (or loadMigrationFiles())
 *             throws a TypeError. Use Nwidart\Modules\Migrations\Migrator to work with module
 *             migrations. Will be removed in v14.
 */
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
