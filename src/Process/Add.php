<?php

namespace Nwidart\Modules\Process;

class Add extends Runner
{
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
