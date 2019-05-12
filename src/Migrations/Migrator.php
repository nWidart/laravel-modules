<?php

namespace Nwidart\Modules\Migrations;

use Nwidart\Modules\Module;
use Nwidart\Modules\Support\Config\GenerateConfigReader;

class Migrator
{
    /**
     * Nwidart Module instance.
     * @var Module
     */
    private $module;

    /**
     * Create new instance.
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get migration path.
     * @return string
     */
    public function getPath(): string
    {
        $config = $this->module->get('migration');

        $migrationPath = GenerateConfigReader::read('migration');
        $path = (is_array($config) && array_key_exists('path', $config)) ? $config['path'] : $migrationPath->getPath();

        return $this->module->getExtraPath($path);
    }
}
