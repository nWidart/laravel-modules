<?php

namespace Nwidart\Modules\Tests\Fixtures\BrokenModules;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('broken-modules.cart', true);
    }
}
