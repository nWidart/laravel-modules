<?php

namespace Nwidart\Modules\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Nwidart\Modules\Generators\ModuleGenerator;

class ModuleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public ModuleGenerator $moduleGenerator)
    {
    }
}
