<?php

namespace Nwidart\Modules\Contracts;

interface RunableInterface
{
    /**
     * Run the specified command.
     *
     * @return void
     */
    public function run(string $command);
}
