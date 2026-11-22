<?php

namespace Nwidart\Modules\Tests\Support\Fixtures;

use Illuminate\Support\ServiceProvider;

class ModuleConfigReadingProvider extends ServiceProvider
{
    public ?string $moduleName = null;

    public function register(): void
    {
        $this->moduleName = config('blog.name');
    }
}
