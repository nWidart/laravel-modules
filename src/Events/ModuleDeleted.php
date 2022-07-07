<?php

namespace Nwidart\Modules\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Nwidart\Modules\Generators\ModuleGenerator;

class ModuleDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $name)
    {
    }
}
