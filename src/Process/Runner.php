<?php

namespace Nwidart\Modules\Process;

use Nwidart\Modules\Contracts\RunableInterface;
use Nwidart\Modules\Repository;

class Runner implements RunableInterface
{
    /**
     * The module instance.
     *
     * @var \Nwidart\Modules\Repository
     */
    protected $module;

    /**
     * The constructor.
     *
     * @param \Nwidart\Modules\Repository $module
     */
    public function __construct(Repository $module)
    {
        $this->module = $module;
    }

    /**
     * Run the given command.
     *
     * @param string $command
     */
    public function run($command)
    {
        passthru($command);
    }
}
