<?php

namespace Nwidart\Modules\Console\Traits;

use Nwidart\Modules\Console\Traits\ConsoleMessages;

trait CommandArguments
{
    use ConsoleMessages;

    /**
     * Get console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return $this->setArguments();
    }

    /**
     * Set the command arguments
     *
     * @return array
     */
    protected function setArguments(): array
    {
        return [];
    }

    /**
     * Set the command options
     *
     * @return array
     */
    protected function setOptions(): array
    {
        return [];
    }

    /**
     * Get console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return $this->setOptions();
    }
}
