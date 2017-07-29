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
        $this->name       = $name;
        $this->repository = $repository;
        $this->console    = $console;
    }

    /**
     * Set the module name
     *
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set console command instance.
     *
     * @param \Illuminate\Console\Command $console
     *
     * @return $this
     */
    public function setConsole(Command $console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Set the module repository instance.
     *
     * @param Repository $repository
     *
     * @return $this
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;

        return $this;
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