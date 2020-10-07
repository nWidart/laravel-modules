<?php

namespace Nwidart\Modules\Console\Traits;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Nwidart\Modules\Console\Traits\CommandArguments;

trait ModuleCommandArguments
{
    use CommandArguments;

    /**
     * Get console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array_merge($this->setArguments(), [
            [$this->argumentName, InputArgument::REQUIRED, 'The name of the resource.']
        ]);
    }

    /**
     * Get console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge($this->setOptions(), [
            ['force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when files already exists.']
        ]);
    }
}
