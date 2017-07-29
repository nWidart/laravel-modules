<?php

namespace Nwidart\Modules\Events;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;
use Nwidart\Modules\Repository;

/**
 * Class ModuleBaseEvent
 * @package Nwidart\Modules\Events
 */
abstract class ModuleBaseEvent
{
    /**
     * The module instance.
     *
     * @var Module
     */
    protected $module;

    /**
     * The console command instance.
     *
     * @var Command
     */
    protected $console;

    public function __construct($name, Repository $repository, Command $console)
    {
        $this->module = $repository->get($name);
        $this->console = $console;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Get the console
     *
     * @return Command
     */
    public function getConsole()
    {
        return $this->console;
    }
}
