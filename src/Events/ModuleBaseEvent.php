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
     * The module name.
     *
     * @var string
     */
    protected $name;
    /**
     * The module repository instance.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * The console command instance.
     *
     * @var Command
     */
    protected $console;

    public function __construct($name, $repository = null, $console = null)
    {
        $this->name = $name;
        $this->repository = $repository;
        $this->console = $console;
    }

    /**
     * Get the module name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->repository->get($this->name);
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
