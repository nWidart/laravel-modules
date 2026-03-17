<?php

namespace Nwidart\Modules\Support;

use Nwidart\Modules\Generators\ModuleGenerator;

abstract class ReplacementKeyCommand
{
    public function __construct(protected ModuleGenerator $generator) {}

    abstract public function handle(): string;
}
