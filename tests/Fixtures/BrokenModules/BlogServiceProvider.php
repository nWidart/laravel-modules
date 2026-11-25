<?php

namespace Nwidart\Modules\Tests\Fixtures\BrokenModules;

use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('broken-modules.blog', true);
    }
}
