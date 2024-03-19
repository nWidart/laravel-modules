<?php

namespace Nwidart\Modules\Commands;

use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ListCommandTest extends BaseTestCase
{
    /**
     * @var string
     */
    private $modulePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    public function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_can_list_modules()
    {
        $code = $this->artisan('module:list');

        // We just want to make sure nothing throws an exception inside the list command
        $this->assertTrue(true);
        $this->assertSame(0, $code);
    }
}
