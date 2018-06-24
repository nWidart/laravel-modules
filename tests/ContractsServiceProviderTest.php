<?php

namespace Nwidart\Modules\Tests;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;

class ContractsServiceProviderTest extends BaseTestCase
{
    /** @test */
    public function it_binds_repository_interface_with_implementation()
    {
        $this->assertInstanceOf(LaravelFileRepository::class, app(RepositoryInterface::class));
    }
}
