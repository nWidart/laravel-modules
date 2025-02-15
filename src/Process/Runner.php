<?php

namespace Nwidart\Modules\Process;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Contracts\RunableInterface;

class Runner implements RunableInterface
{
    /**
     * The module instance.
     */
    protected RepositoryInterface $module;

    public function __construct(RepositoryInterface $module)
    {
        $this->module = $module;
    }

    /**
     * Run the given command.
     */
    public function run(string $command)
    {
        passthru($command);
    }
}
