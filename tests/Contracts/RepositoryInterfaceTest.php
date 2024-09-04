<?php

namespace Nwidart\Modules\Tests\Contracts;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Tests\BaseTestCase;

class RepositoryInterfaceTest extends BaseTestCase
{
    public function test_it_binds_repository_interface_with_implementation()
    {
        $this->assertInstanceOf(LaravelFileRepository::class, app(RepositoryInterface::class));
    }
}
