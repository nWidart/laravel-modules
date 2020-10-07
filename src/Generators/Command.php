<?php

namespace Nwidart\Modules\Generators;

use Illuminate\Support\Str;
use Nwidart\Modules\Commands\GeneratorCommand;

abstract class Command extends GeneratorCommand
{

    /**
     * The name to be appended to the generated resources.
     *
     * @var null|string
     */
    protected $appendable;

    /**
     * Getter for appendable
     *
     * @return void
     */
    public function appendable()
    {
        return $this->appendable;
    }

    /**
     * Get and resolve the filename.
     *
     * @return string
     */
    protected function getFileName(): string
    {

        $name = Str::studly($this->argument($this->argumentName));
        if ($this->appendable() && !Str::contains(strtolower($name), strtolower($this->appendable()))) {
            $name .= Str::studly($this->appendable());
        }

        return Str::singular(Str::studly($name));
    }
}
