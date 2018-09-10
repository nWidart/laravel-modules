<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Laravel\Contracts\RepositoryInterface;

class LaravelModulesServiceProviderTest extends BaseTestCase
{
    /** @test */
    public function it_binds_modules_key_to_repository_class()
    {
        $this->assertInstanceOf(Contracts\RepositoryInterface::class, app('modules'));
    }
}
