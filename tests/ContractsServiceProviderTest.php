<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\Repository;

class ContractsServiceProviderTest extends BaseTestCase
{
    /** @test */
    public function it_binds_repository_interface_with_implementation()
    {
        $this->assertInstanceOf(Repository::class, app(RepositoryInterface::class));
    }
}
