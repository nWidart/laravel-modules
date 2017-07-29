<?php

namespace Nwidart\Modules\Events;

use Illuminate\Console\Command;
use Nwidart\Modules\Module;

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

    public function __construct(Module $module, Command $console)
    {
        $this->module = $module;
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
